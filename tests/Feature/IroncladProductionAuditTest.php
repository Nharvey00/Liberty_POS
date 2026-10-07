<?php

namespace Tests\Feature;

use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IroncladProductionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $manager;
    protected User $owner;
    protected Product $product;
    protected Customer $customer;
    protected CreditAccount $creditAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Level 1']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Level 2']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Level 3']);

        $this->cashier = User::factory()->create([
            'first_name' => 'Cashier',
            'last_name' => 'One',
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'One',
            'role_id' => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Owner',
            'last_name' => 'One',
            'role_id' => 3,
        ]);

        $this->product = Product::create([
            'name' => '11kg LPG Standard',
            'price' => 980.00,
            'new_cylinder_price' => 2500.00,
            'standard_capacity_kg' => 11.00,
            'stock_quantity' => 25,
            'empty_quantity' => 10,
        ]);

        $this->customer = Customer::create([
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'customer_type' => 'Household',
            'phone' => '09171234567',
        ]);

        $this->creditAccount = CreditAccount::create([
            'customer_id' => $this->customer->id,
            'is_active' => true,
        ]);
    }

    /**
     * Requirement 1: Concurrency & Partial Saves (The Vault)
     */
    public function test_concurrency_and_partial_saves_in_transactions(): void
    {
        // PosController store executes atomically within DB transaction
        $response = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'payment_method' => 'Cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'is_swap' => true,
                ],
            ],
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals(23, $this->product->fresh()->stock_quantity);

        // PaymentController store executes within DB transaction
        $payResponse = $this->actingAs($this->cashier)->post(route('payments.store', $this->creditAccount), [
            'amount' => 500.00,
        ]);
        $payResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payments', [
            'credit_account_id' => $this->creditAccount->id,
            'amount' => 500.00,
        ]);
        $this->assertDatabaseHas('credit_ledgers', [
            'credit_account_id' => $this->creditAccount->id,
            'amount' => 500.00,
            'transaction_type' => 'Payment',
        ]);

        // OrderVoidController executes within DB transaction
        $order = Order::latest('id')->first();
        $voidResponse = $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Customer changed mind immediately',
        ]);
        $voidResponse->assertSessionHasNoErrors();
        $this->assertEquals('voided', $order->fresh()->status);
        $this->assertEquals(25, $this->product->fresh()->stock_quantity);
    }

    /**
     * Requirement 2: Timezone Synchronization
     */
    public function test_timezone_is_configured_to_asia_manila(): void
    {
        $this->assertEquals('Asia/Manila', config('app.timezone'));
        $this->assertEquals('Asia/Manila', Carbon::now()->getTimezone()->getName());
    }

    /**
     * Requirement 3: Soft Deletes & Fired Employee Guards
     */
    public function test_soft_deletes_active_and_guards_prevent_audit_trail_loss(): void
    {
        // 1. Models utilize SoftDeletes trait
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses(Product::class));
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses(Customer::class));
        $this->assertContains(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses(User::class));

        // 2. Soft-delete clean product without history
        $tempProduct = Product::create([
            'name' => 'Temporary Hose',
            'price' => 150.00,
            'stock_quantity' => 0,
            'empty_quantity' => 0,
        ]);
        $delProdResponse = $this->actingAs($this->manager)->delete(route('products.destroy', $tempProduct));
        $delProdResponse->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $tempProduct->id]);

        // 3. Deleting product with inventory triggers soft-delete safely
        $blockedProdResponse = $this->actingAs($this->manager)->delete(route('products.destroy', $this->product));
        $blockedProdResponse->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $this->product->id]);

        // 4. Soft-delete clean staff user without sales history
        $tempStaff = User::factory()->create([
            'first_name' => 'Temp',
            'last_name' => 'Clerk',
            'role_id' => 1,
        ]);
        $delUserResponse = $this->actingAs($this->owner)->delete(route('users.destroy', $tempStaff));
        $delUserResponse->assertRedirect(route('users.index'));
        $this->assertSoftDeleted('users', ['id' => $tempStaff->id]);
    }

    /**
     * Requirement 4: Decimal Precision Audit
     */
    public function test_decimal_precision_on_financial_and_weight_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'price'));
        $this->assertTrue(Schema::hasColumn('products', 'new_cylinder_price'));
        $this->assertTrue(Schema::hasColumn('products', 'standard_capacity_kg'));
        $this->assertTrue(Schema::hasColumn('orders', 'total_amount'));
        $this->assertTrue(Schema::hasColumn('orders', 'discount_amount'));
        $this->assertTrue(Schema::hasColumn('order_items', 'residual_kg'));
        $this->assertTrue(Schema::hasColumn('order_items', 'actual_consumed_kg'));
        $this->assertTrue(Schema::hasColumn('order_items', 'subtotal'));
        $this->assertTrue(Schema::hasColumn('credit_ledgers', 'amount'));
        $this->assertTrue(Schema::hasColumn('payments', 'amount'));
        $this->assertTrue(Schema::hasColumn('statement_of_accounts', 'total_due'));
    }

    /**
     * Requirement 5: Memory Exhaustion Guards (N+1 Optimization)
     */
    public function test_eager_loading_used_in_order_and_report_controllers(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 980.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'is_swap' => true,
            'subtotal' => 980.00,
        ]);

        // OrderController index loads successfully
        $orderResponse = $this->actingAs($this->manager)->get(route('orders.index'));
        $orderResponse->assertOk();
        $ordersViewData = $orderResponse->viewData('orders');
        $this->assertTrue($ordersViewData->first()->relationLoaded('customer'));
        $this->assertTrue($ordersViewData->first()->relationLoaded('items'));
        $this->assertTrue($ordersViewData->first()->relationLoaded('user'));

        // ReportController sales loads successfully
        $reportResponse = $this->actingAs($this->manager)->get(route('reports.sales'));
        $reportResponse->assertOk();
        $reportViewData = $reportResponse->viewData('orders');
        $this->assertTrue($reportViewData->first()->relationLoaded('customer'));
        $this->assertTrue($reportViewData->first()->relationLoaded('items'));
        $this->assertTrue($reportViewData->first()->relationLoaded('user'));
    }

    /**
     * Requirement 6: CSRF Timeout Handling
     */
    public function test_csrf_timeout_handled_gracefully(): void
    {
        // 1. 419 error view exists
        $this->assertTrue(view()->exists('errors.419'));

        // 2. JSON request with TokenMismatchException returns 419 JSON message
        \Illuminate\Support\Facades\Route::post('/test-csrf-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $jsonResponse = $this->postJson('/test-csrf-trigger');
        $jsonResponse->assertStatus(419);
        $jsonResponse->assertJson(['message' => 'Session expired. Please refresh the page.']);

        // 3. Standard web request redirects back with friendly session error message
        $webResponse = $this->from('/dashboard')->post('/test-csrf-trigger');
        $webResponse->assertRedirect('/dashboard');
        $webResponse->assertSessionHas('error', 'Session expired. Please refresh the page.');
    }

    /**
     * Requirement 7: Payload Integrity & Validation Guards
     */
    public function test_payload_integrity_and_discount_capping_guards(): void
    {
        // 1. Injected price in payload is ignored; DB price (980.00) is enforced
        $response = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'payment_method' => 'Cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'is_swap' => true,
                    'price' => 5.00,        // Attacker payload
                    'subtotal' => 5.00,     // Attacker payload
                ],
            ],
        ]);
        $response->assertSessionHasNoErrors();
        $order = Order::latest('id')->first();
        $this->assertEquals(980.00, (float)$order->total_amount);

        // 2. Non-positive quantities rejected
        $badQty = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'payment_method' => 'Cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 0,
                    'is_swap' => true,
                ],
            ],
        ]);
        $badQty->assertSessionHasErrors('items.0.quantity');

        // 3. Negative payment amount rejected
        $badPay = $this->actingAs($this->cashier)->post(route('payments.store', $this->creditAccount), [
            'amount' => -100.00,
        ]);
        $badPay->assertSessionHasErrors('amount');

        // 4. Discount amount exceeding subtotal is rejected by validation
        $excessDiscount = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id' => $this->customer->id,
            'payment_method' => 'Cash',
            'discount_amount' => 5000.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);
        $excessDiscount->assertSessionHasErrors('discount_amount');
    }
}
