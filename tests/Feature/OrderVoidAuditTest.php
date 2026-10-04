<?php

namespace Tests\Feature;

use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderVoidAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $manager;
    protected Product $cylinderProduct;
    protected Product $accessoryProduct;
    protected Customer $customer;
    protected CreditAccount $creditAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Level 1']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Level 2']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Level 3']);

        $this->cashier = User::factory()->create([
            'first_name' => 'Maria',
            'last_name' => 'Cashier',
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Alex',
            'last_name' => 'Manager',
            'role_id' => 2,
        ]);

        $this->cylinderProduct = Product::create([
            'name' => '50kg Industrial Cylinder Refill',
            'price' => 1800.00,
            'new_cylinder_price' => 4500.00,
            'standard_capacity_kg' => 50.0,
            'stock_quantity' => 20,
            'empty_quantity' => 10,
        ]);

        $this->accessoryProduct = Product::create([
            'name' => 'High-Pressure Hose 2m',
            'price' => 350.00,
            'new_cylinder_price' => null,
            'standard_capacity_kg' => null,
            'stock_quantity' => 30,
            'empty_quantity' => 0,
        ]);

        $this->customer = Customer::create([
            'first_name' => 'Davao',
            'last_name' => 'Bakery Corp',
            'business_name' => 'Davao Bakery Corp',
            'customer_type' => 'Commercial',
            'phone' => '09171234567',
        ]);

        $this->creditAccount = CreditAccount::create([
            'customer_id' => $this->customer->id,
            'agreed_monthly_payment' => 50000.00,
            'is_active' => true,
        ]);
    }

    /**
     * Massive end-to-end integration test verifying complete Void lifecycle reversal:
     * 1. Credit Sale of ₱9,000 for 5 Cylinders (with 5 empty shells swapped)
     * 2. Assert Dashboard = ₱9,000, Customer Balance = ₱9,000, Stock -5, Shells +5
     * 3. Trigger Void action
     * 4. Assert Dashboard = ₱0, Customer Balance = ₱0, Stock +5, Shells -5
     */
    public function test_end_to_end_void_lifecycle_reverses_financial_stock_and_credit_modules(): void
    {
        // -------------------------------------------------------------
        // STEP 1: Process POS Credit Sale (5 Cylinders @ ₱1,800 = ₱9,000)
        // -------------------------------------------------------------
        $postData = [
            'customer_id' => $this->customer->id,
            'payment_method' => 'Credit',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 5,
                    'is_swap' => true,
                ],
            ],
        ];

        $checkoutResponse = $this->actingAs($this->cashier)->post(route('pos.store'), $postData);
        $checkoutResponse->assertRedirect();

        $order = Order::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(9000.00, (float) $order->total_amount);
        $this->assertEquals('completed', $order->status);

        // -------------------------------------------------------------
        // STEP 2: Pre-Void Verification
        // -------------------------------------------------------------
        // 1. Stock decreased by 5 (20 -> 15)
        // 2. Empty shells increased by 5 (10 -> 15)
        $this->cylinderProduct->refresh();
        $this->assertEquals(15, $this->cylinderProduct->stock_quantity);
        $this->assertEquals(15, $this->cylinderProduct->empty_quantity);

        // 3. Customer credit balance = ₱9,000
        $this->creditAccount->refresh();
        $this->assertEquals(9000.00, (float) $this->creditAccount->remaining_balance);

        // 4. Dashboard shows ₱9,000.00 revenue and 1 transaction
        $dashResponsePre = $this->actingAs($this->manager)->get(route('dashboard'));
        $dashResponsePre->assertStatus(200);
        $dashResponsePre->assertSee('₱9,000.00');
        $dashResponsePre->assertSee('1 Transactions');

        // Verify Order model scopes directly
        $this->assertEquals(9000.00, (float) Order::valid()->whereDate('created_at', Carbon::today())->sum('total_amount'));
        $this->assertEquals(1, Order::valid()->whereDate('created_at', Carbon::today())->count());

        // -------------------------------------------------------------
        // STEP 3: Trigger Void Action as Manager
        // -------------------------------------------------------------
        $voidResponse = $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Customer requested order cancellation due to delivery scheduling issue',
        ]);
        $voidResponse->assertRedirect(route('orders.show', $order));
        $voidResponse->assertSessionHas('success');

        // -------------------------------------------------------------
        // STEP 4: Post-Void Comprehensive Reversal Assertions
        // -------------------------------------------------------------
        $order->refresh();
        $this->assertEquals('voided', $order->status);
        $this->assertEquals($this->manager->id, $order->voided_by);
        $this->assertNotNull($order->voided_at);

        // 1. Stock restored (+5 back to 20)
        $this->cylinderProduct->refresh();
        $this->assertEquals(20, $this->cylinderProduct->stock_quantity);

        // 2. Empty shells returned (-5 back to 10)
        $this->assertEquals(10, $this->cylinderProduct->empty_quantity);

        // 3. Inventory Stock Movement Audit Log created
        $stockInLog = StockIn::where('product_id', $this->cylinderProduct->id)
            ->where('remarks', 'like', '%Void Reversal%')
            ->first();
        $this->assertNotNull($stockInLog, 'Stock In audit trail log must be recorded for void reversal');
        $this->assertEquals(5, $stockInLog->quantity_received);

        // 4. Customer credit balance = ₱0.00 (Reversing Payment ledger created)
        $this->creditAccount->refresh();
        $this->assertEquals(0.00, (float) $this->creditAccount->remaining_balance);

        $reversalPayment = CreditLedger::where('order_id', $order->id)
            ->where('transaction_type', 'Payment')
            ->first();
        $this->assertNotNull($reversalPayment);
        $this->assertEquals(9000.00, (float) $reversalPayment->amount);

        // 5. Dashboard reflects ₱0.00 revenue and 0 transactions
        $dashResponsePost = $this->actingAs($this->manager)->get(route('dashboard'));
        $dashResponsePost->assertStatus(200);
        $dashResponsePost->assertSee('₱0.00');
        $dashResponsePost->assertSee('0 Transactions');
        $dashResponsePost->assertDontSee('₱9,000.00');

        $this->assertEquals(0.00, (float) Order::valid()->whereDate('created_at', Carbon::today())->sum('total_amount'));
        $this->assertEquals(0, Order::valid()->whereDate('created_at', Carbon::today())->count());

        // 6. Reports Module: sales totals exclude voided orders
        $reportsResponse = $this->actingAs($this->manager)->get(route('reports.sales'));
        $reportsResponse->assertStatus(200);
        $reportsResponse->assertSee('VOIDED');
        $reportsResponse->assertViewHas('totalSales', 0.0);
        $reportsResponse->assertViewHas('orderCount', 0);

        // 7. Excel Sales Export excludes voided orders from revenue
        $exportResponse = $this->actingAs($this->manager)->get(route('reports.sales.export'));
        $exportResponse->assertStatus(200);
        $content = $exportResponse->streamedContent();
        $this->assertStringNotContainsString('9000.00', $content);
    }

    /**
     * Test voiding a mixed cart order with cylinders and accessories.
     */
    public function test_void_lifecycle_mixed_cart_handles_accessories_and_cylinders(): void
    {
        // Cash Sale with 1 cylinder (with swap) and 2 accessories
        $order = Order::create([
            'customer_id' => null,
            'user_id' => $this->cashier->id,
            'payment_method' => 'Cash',
            'total_amount' => 2500.00, // 1800 + (2 * 350)
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->cylinderProduct->id,
            'quantity' => 1,
            'is_swap' => true,
            'subtotal' => 1800.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->accessoryProduct->id,
            'quantity' => 2,
            'is_swap' => false,
            'subtotal' => 700.00,
        ]);

        // Adjust stock to simulate post-checkout state
        $this->cylinderProduct->decrement('stock_quantity', 1);
        $this->cylinderProduct->increment('empty_quantity', 1);
        $this->accessoryProduct->decrement('stock_quantity', 2);

        $this->assertEquals(19, $this->cylinderProduct->fresh()->stock_quantity);
        $this->assertEquals(11, $this->cylinderProduct->fresh()->empty_quantity);
        $this->assertEquals(28, $this->accessoryProduct->fresh()->stock_quantity);
        $this->assertEquals(0, $this->accessoryProduct->fresh()->empty_quantity);

        // Pre-void: Dashboard revenue = 2500
        $this->assertEquals(2500.00, (float) Order::valid()->whereDate('created_at', Carbon::today())->sum('total_amount'));

        // Void the order
        $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Customer returned all items immediately for cash refund',
        ]);

        // Post-void checks:
        // 1. Cylinder: stock returned (+1 -> 20), empty deducted (-1 -> 10)
        $this->assertEquals(20, $this->cylinderProduct->fresh()->stock_quantity);
        $this->assertEquals(10, $this->cylinderProduct->fresh()->empty_quantity);

        // 2. Accessory: stock returned (+2 -> 30), empty untouched at 0
        $this->assertEquals(30, $this->accessoryProduct->fresh()->stock_quantity);
        $this->assertEquals(0, $this->accessoryProduct->fresh()->empty_quantity);

        // 3. Dashboard revenue = 0
        $this->assertEquals(0.00, (float) Order::valid()->whereDate('created_at', Carbon::today())->sum('total_amount'));

        // 4. Movement logs created for both items
        $this->assertTrue(StockIn::where('product_id', $this->cylinderProduct->id)->where('remarks', 'like', '%Void Reversal%')->exists());
        $this->assertTrue(StockIn::where('product_id', $this->accessoryProduct->id)->where('remarks', 'like', '%Void Reversal%')->exists());
    }
}
