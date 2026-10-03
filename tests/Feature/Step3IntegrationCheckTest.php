<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Step3IntegrationCheckTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $manager;
    protected User $owner;
    protected Product $cylinderProduct;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Cashier']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Manager']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Owner']);

        $this->cashier = User::factory()->create(['role_id' => 1]);
        $this->manager = User::factory()->create(['role_id' => 2]);
        $this->owner = User::factory()->create(['role_id' => 3]);

        $this->cylinderProduct = Product::create([
            'name' => '11kg Test Cylinder',
            'price' => 950.00,
            'new_cylinder_price' => 2200.00,
            'standard_capacity_kg' => 11.00,
            'stock_quantity' => 50,
            'empty_quantity' => 10,
        ]);
    }

    /**
     * CHECK 1: Cashiers have view-only access to inventory.
     * Edit/Delete buttons are hidden, and management routes return 403.
     */
    public function test_cashier_has_view_only_inventory_access(): void
    {
        // 1. Cashier can view products index
        $indexResponse = $this->actingAs($this->cashier)->get(route('products.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($this->cylinderProduct->name);
        // Ensure Edit button is not rendered for cashier on index
        $indexResponse->assertDontSee(route('products.edit', $this->cylinderProduct));

        // 2. Cashier can view product details
        $showResponse = $this->actingAs($this->cashier)->get(route('products.show', $this->cylinderProduct));
        $showResponse->assertStatus(200);
        // Ensure Edit button is hidden on show page as well
        $showResponse->assertDontSee('Edit Details');

        // 3. Cashier cannot access edit form (403 Forbidden)
        $editResponse = $this->actingAs($this->cashier)->get(route('products.edit', $this->cylinderProduct));
        $editResponse->assertStatus(403);

        // 4. Cashier cannot delete product (403 Forbidden)
        $deleteResponse = $this->actingAs($this->cashier)->delete(route('products.destroy', $this->cylinderProduct));
        $deleteResponse->assertStatus(403);
    }

    /**
     * CHECK 2: Order Void system properly restores stock_quantity and reverses credit_ledgers.
     */
    public function test_order_void_restores_stock_and_reverses_credit_ledger(): void
    {
        $customer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'customer_type' => 'Household',
        ]);

        $account = CreditAccount::create([
            'customer_id' => $customer->id,
            'agreed_monthly_payment' => 1500,
            'is_active' => true,
        ]);

        $initialStock = $this->cylinderProduct->stock_quantity;
        $initialEmpty = $this->cylinderProduct->empty_quantity;

        // Perform POS Credit purchase with swap
        $checkoutData = [
            'customer_id' => $customer->id,
            'payment_method' => 'Credit',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 2,
                    'is_swap' => true,
                ],
            ],
        ];

        $checkoutResponse = $this->actingAs($this->cashier)->post(route('pos.store'), $checkoutData);
        $checkoutResponse->assertRedirect();

        $order = Order::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('completed', $order->status);

        // Verify stock deducted and empty quantity increased
        $this->cylinderProduct->refresh();
        $this->assertEquals($initialStock - 2, $this->cylinderProduct->stock_quantity);
        $this->assertEquals($initialEmpty + 2, $this->cylinderProduct->empty_quantity);

        // Verify credit charge was recorded
        $charge = CreditLedger::where('order_id', $order->id)->where('transaction_type', 'Charge')->first();
        $this->assertNotNull($charge);
        $this->assertEquals($order->total_amount, $charge->amount);

        // Now VOID the order as Manager
        $voidResponse = $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Customer cancelled the delivery due to duplicate order',
        ]);
        $voidResponse->assertRedirect(route('orders.show', $order));

        // Verify Order status is now 'voided' with audit trail
        $order->refresh();
        $this->assertEquals('voided', $order->status);
        $this->assertEquals($this->manager->id, $order->voided_by);
        $this->assertNotNull($order->voided_at);
        $this->assertEquals('Customer cancelled the delivery due to duplicate order', $order->void_reason);

        // Verify stock quantity is restored and empty quantity decremented
        $this->cylinderProduct->refresh();
        $this->assertEquals($initialStock, $this->cylinderProduct->stock_quantity);
        $this->assertEquals($initialEmpty, $this->cylinderProduct->empty_quantity);

        // Verify reversing Payment ledger is inserted
        $reversal = CreditLedger::where('order_id', $order->id)
            ->where('transaction_type', 'Payment')
            ->first();
        $this->assertNotNull($reversal);
        $this->assertEquals($order->total_amount, $reversal->amount);

        // Remaining balance of account should be zero
        $this->assertEquals(0, $account->remaining_balance);
    }

    /**
     * CHECK 3: POS checkout dynamically enforces Senior ID requirement when Senior Citizen discount is selected.
     */
    public function test_pos_enforces_senior_id_when_senior_discount_applied(): void
    {
        $customer = Customer::create([
            'first_name' => 'Lolo',
            'last_name' => 'Dela Cruz',
            'customer_type' => 'Household',
        ]);

        // Attempt checkout with Senior Citizen discount BUT missing Senior ID -> MUST FAIL
        $responseFailed = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_amount' => 50,
            'discount_type' => 'senior',
            'senior_id' => '', // Empty!
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);

        $responseFailed->assertSessionHasErrors(['senior_id']);

        // Attempt checkout with Senior Citizen discount WITH valid Senior ID -> MUST SUCCEED
        $responseSuccess = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_amount' => 50,
            'discount_type' => 'senior',
            'senior_id' => 'OSCA-DVO-12345',
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);

        $responseSuccess->assertSessionHasNoErrors();
        $order = Order::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals('senior', $order->discount_type);
        $this->assertEquals('OSCA-DVO-12345', $order->senior_id);
        $this->assertEquals(50.00, $order->discount_amount);
    }

    /**
     * CHECK 4: Zero-Dependency Excel Export streams CSV with UTF-8 BOM, chunking, and headers.
     */
    public function test_excel_sales_export_streams_csv_with_utf8_bom_and_chunking(): void
    {
        $customer = Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'Luna',
            'business_name' => 'Luna Grill',
            'customer_type' => 'Commercial',
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $this->manager->id,
            'invoice_number' => 'INV-TEST-99999',
            'total_amount' => 1120.00,
            'discount_amount' => 0.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        // Manager accesses export route
        $response = $this->actingAs($this->manager)->get(route('reports.sales.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        // Capture streamed content
        $content = $response->streamedContent();

        // 1. Verify UTF-8 Byte Order Mark (BOM) is present at the start
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // 2. Verify column headers
        $this->assertStringContainsString('Invoice #', $content);
        $this->assertStringContainsString('Subtotal (Vatable)', $content);
        $this->assertStringContainsString('12% VAT', $content);
        $this->assertStringContainsString('Total Amount (PHP)', $content);

        // 3. Verify order data row
        $this->assertStringContainsString('INV-TEST-99999', $content);
        $this->assertStringContainsString('Juan Luna', $content);
        $this->assertStringContainsString('Luna Grill', $content);
        $this->assertStringContainsString('1000.00', $content); // 1120 / 1.12
        $this->assertStringContainsString('120.00', $content);  // VAT
        $this->assertStringContainsString('1120.00', $content); // Total
    }

    /**
     * CHECK 5: Cashier is strictly blocked from the sales export route (403 Forbidden).
     */
    public function test_cashier_is_strictly_blocked_from_excel_export(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('reports.sales.export'));
        $response->assertStatus(403);
    }
}
