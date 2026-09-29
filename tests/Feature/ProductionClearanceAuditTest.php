<?php

namespace Tests\Feature;

use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\StatementOfAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionClearanceAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $manager;
    protected User $owner;
    protected Product $cylinderProduct;
    protected Product $accessoryProduct;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed core roles
        $cashierRole = Role::firstOrCreate(['id' => 1], ['role_name' => 'Level 1']);
        $managerRole = Role::firstOrCreate(['id' => 2], ['role_name' => 'Level 2']);
        $ownerRole   = Role::firstOrCreate(['id' => 3], ['role_name' => 'Level 3']);

        $this->cashier = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Cashier',
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Mark',
            'last_name' => 'Manager',
            'role_id' => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'Owner',
            'role_id' => 3,
        ]);

        // Standard 11kg LPG Cylinder (price = 990, standard_capacity = 11kg -> 90/kg)
        $this->cylinderProduct = Product::create([
            'name' => '11kg LPG Cylinder Test',
            'sku' => 'LPG-11-TEST',
            'category' => 'Cylinder',
            'price' => 990.00,
            'stock_quantity' => 20,
            'empty_quantity' => 10,
            'standard_capacity_kg' => 11.00,
            'tare_weight_kg' => 14.50,
            'new_cylinder_price' => 1500.00,
        ]);

        // Accessory with no standard capacity
        $this->accessoryProduct = Product::create([
            'name' => 'LPG Regulator Test',
            'sku' => 'ACC-REG-TEST',
            'category' => 'Accessory',
            'price' => 350.00,
            'stock_quantity' => 15,
            'empty_quantity' => 0,
            'standard_capacity_kg' => null,
            'tare_weight_kg' => null,
            'new_cylinder_price' => null,
        ]);
    }

    // ==========================================
    // PILLAR 1: RBAC & SECURITY AUDIT
    // ==========================================

    public function test_cashier_is_strictly_blocked_from_restricted_routes(): void
    {
        // Staff/User Management
        $this->actingAs($this->cashier)->get('/users')->assertStatus(403);
        $this->actingAs($this->cashier)->post('/users', [])->assertStatus(403);

        // Stock Adjustments
        $this->actingAs($this->cashier)->get('/stock-ins')->assertStatus(403);
        $this->actingAs($this->cashier)->get('/stock-outs')->assertStatus(403);

        // Ledger & History
        $this->actingAs($this->cashier)->get('/orders')->assertStatus(403);
        $this->actingAs($this->cashier)->get('/customers')->assertStatus(403);
        $this->actingAs($this->cashier)->get('/products')->assertStatus(403);
        $this->actingAs($this->cashier)->get('/credit-accounts')->assertStatus(403);
        $this->actingAs($this->cashier)->get('/statements')->assertStatus(403);
    }

    public function test_cashier_permitted_exceptions_pos_payments_dashboard(): void
    {
        // 1. POS Checkout page
        $this->actingAs($this->cashier)->get('/pos')->assertStatus(200);

        // 2. Dashboard with sales metrics
        $response = $this->actingAs($this->cashier)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee("Today's Sales", false);
        // Ensure Active Credit Accounts metric is NOT leaked to Cashier
        $response->assertDontSee("Utang ledger active");

        // 3. Record Utang Cash Payment
        $customer = Customer::create([
            'first_name' => 'Debt',
            'last_name' => 'Payer',
            'customer_type' => 'Normal',
        ]);
        $account = CreditAccount::create([
            'customer_id' => $customer->id,
            'credit_limit' => 5000,
            'current_balance' => 1500,
            'is_active' => true,
        ]);

        $this->actingAs($this->cashier)->get(route('payments.create', $account))->assertStatus(200);
    }

    public function test_manager_has_operational_access_but_blocked_from_user_admin(): void
    {
        $this->actingAs($this->manager)->get('/products')->assertStatus(200);
        $this->actingAs($this->manager)->get('/customers')->assertStatus(200);
        $this->actingAs($this->manager)->get('/orders')->assertStatus(200);
        $this->actingAs($this->manager)->get('/stock-ins')->assertStatus(200);
        $this->actingAs($this->manager)->get('/stock-outs')->assertStatus(200);
        $this->actingAs($this->manager)->get('/credit-accounts')->assertStatus(200);
        $this->actingAs($this->manager)->get('/statements')->assertStatus(200);

        // Hard Block from User Management
        $this->actingAs($this->manager)->get('/users')->assertStatus(403);
    }

    public function test_owner_has_full_unrestricted_access(): void
    {
        $this->actingAs($this->owner)->get('/users')->assertStatus(200);
        $this->actingAs($this->owner)->get('/products')->assertStatus(200);
        $this->actingAs($this->owner)->get('/pos')->assertStatus(200);
    }

    // ==========================================
    // PILLAR 2: TRANSACTION & POS LOGIC AUDIT
    // ==========================================

    public function test_normal_walkin_cash_sale_processes_cleanly(): void
    {
        $initialStock = $this->cylinderProduct->stock_quantity;

        $response = $this->actingAs($this->cashier)->post('/pos/checkout', [
            'customer_id' => null,
            'payment_method' => 'Cash',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 2,
                    'is_swap' => true,
                    'residual_kg' => null,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertNull($order->customer_id);
        $this->assertEquals('Cash', $order->payment_method);
        $this->assertEquals(1980.00, (float)$order->total_amount); // 990 * 2

        $this->cylinderProduct->refresh();
        $this->assertEquals($initialStock - 2, $this->cylinderProduct->stock_quantity);

        $item = $order->items->first();
        $this->assertNull($item->actual_consumed_kg);
        $this->assertNull($item->residual_kg);
    }

    public function test_new_cylinder_purchase_without_swap_bills_flat_new_cylinder_price_only(): void
    {
        $initialStock = $this->cylinderProduct->stock_quantity;
        $initialEmpty = $this->cylinderProduct->empty_quantity;

        // Buying brand new tank with gas: is_swap = false
        // Product has refill price = 990.00 and new_cylinder_price = 1500.00
        // Expected subtotal must be strictly 1500.00 (NOT 990 + 1500 = 2490)
        $response = $this->actingAs($this->cashier)->post('/pos/checkout', [
            'customer_id' => null,
            'payment_method' => 'Cash',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => false,
                    'residual_kg' => null,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $order = Order::latest()->first();
        $this->assertEquals(1500.00, (float)$order->total_amount);

        $this->cylinderProduct->refresh();
        // Filled stock decremented
        $this->assertEquals($initialStock - 1, $this->cylinderProduct->stock_quantity);
        // Empty stock unchanged because no empty cylinder was surrendered
        $this->assertEquals($initialEmpty, $this->cylinderProduct->empty_quantity);
    }

    public function test_corporate_swap_credit_enforces_qty_1_and_calculates_residual_charge(): void
    {
        $companyCustomer = Customer::create([
            'first_name' => 'Corporate',
            'last_name' => 'Client',
            'business_name' => 'San Miguel Corp',
            'customer_type' => 'Company',
        ]);
        $creditAccount = CreditAccount::create([
            'customer_id' => $companyCustomer->id,
            'credit_limit' => 50000,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        // Attempting Corporate Swap with quantity = 2 must fail validation
        $responseFailed = $this->actingAs($this->cashier)->post('/pos/checkout', [
            'customer_id' => $companyCustomer->id,
            'payment_method' => 'Credit',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 2, // VIOLATION
                    'is_swap' => true,
                    'residual_kg' => 3.0,
                ],
            ],
        ]);
        $responseFailed->assertSessionHasErrors('items.0.quantity');

        // Valid Corporate Swap: quantity = 1, residual_kg = 3.0 kg
        // Consumed = 11.0 - 3.0 = 8.0 kg.
        // Rate = 990 / 11 = 90 / kg.
        // Charge = 8.0 * 90 = 720.00.
        $responseSuccess = $this->actingAs($this->cashier)->post('/pos/checkout', [
            'customer_id' => $companyCustomer->id,
            'payment_method' => 'Credit',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => true,
                    'residual_kg' => 3.0,
                ],
            ],
        ]);
        $responseSuccess->assertSessionHasNoErrors();

        $order = Order::latest()->first();
        $this->assertEquals(720.00, (float)$order->total_amount);

        // Verify Credit Ledger charge
        $ledger = CreditLedger::where('order_id', $order->id)->first();
        $this->assertNotNull($ledger);
        $this->assertEquals('Charge', $ledger->transaction_type);
        $this->assertEquals(720.00, (float)$ledger->amount);
    }

    public function test_pos_strictly_aborts_on_insufficient_stock(): void
    {
        $this->cylinderProduct->update(['stock_quantity' => 2]);

        $response = $this->actingAs($this->cashier)->post('/pos/checkout', [
            'customer_id' => null,
            'payment_method' => 'Cash',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 5, // Exceeds stock of 2
                    'is_swap' => true,
                    'residual_kg' => null,
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(2, $this->cylinderProduct->fresh()->stock_quantity);
    }

    public function test_discount_cannot_exceed_subtotal(): void
    {
        $response = $this->actingAs($this->cashier)->post('/pos/checkout', [
            'customer_id' => null,
            'payment_method' => 'Cash',
            'discount_amount' => 1500.00, // Exceeds 990 subtotal
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => true,
                    'residual_kg' => null,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('discount_amount');
    }

    // ==========================================
    // PILLAR 3: CREDIT LEDGER & SOA AUDIT
    // ==========================================

    public function test_suspended_account_blocks_pos_credit_but_allows_cash_payment(): void
    {
        $customer = Customer::create([
            'first_name' => 'Suspended',
            'last_name' => 'User',
            'customer_type' => 'Normal',
        ]);
        $account = CreditAccount::create([
            'customer_id' => $customer->id,
            'credit_limit' => 5000,
            'current_balance' => 2500,
            'is_active' => false, // SUSPENDED
        ]);

        // 1. POS Credit checkout is blocked
        $posResponse = $this->actingAs($this->cashier)->post('/pos/checkout', [
            'customer_id' => $customer->id,
            'payment_method' => 'Credit',
            'discount_amount' => 0,
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);
        $posResponse->assertSessionHasErrors();

        // 2. Recording cash debt payment is ALLOWED
        $paymentResponse = $this->actingAs($this->cashier)->post(route('payments.store', $account), [
            'amount' => 1000.00,
            'notes' => 'Settling debt on suspended account',
        ]);
        $paymentResponse->assertSessionHasNoErrors();

        // Verify ledger entry created
        $paymentLedger = CreditLedger::where('credit_account_id', $account->id)
            ->where('transaction_type', 'Payment')
            ->first();
        $this->assertNotNull($paymentLedger);
        $this->assertEquals(1000.00, (float)$paymentLedger->amount);
    }

    public function test_customer_deletion_strictly_blocked_if_history_or_balance_exists(): void
    {
        $customer = Customer::create([
            'first_name' => 'Locked',
            'last_name' => 'Customer',
            'customer_type' => 'Normal',
        ]);
        $account = CreditAccount::create([
            'customer_id' => $customer->id,
            'credit_limit' => 5000,
            'current_balance' => 0,
            'is_active' => true,
        ]);
        CreditLedger::create([
            'credit_account_id' => $account->id,
            'transaction_type' => 'Charge',
            'amount' => 500.00,
        ]);

        // Attempt deletion as Manager
        $response = $this->actingAs($this->manager)->delete(route('customers.destroy', $customer));
        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);

        // Empty customer with no history can be deleted
        $cleanCustomer = Customer::create([
            'first_name' => 'Transient',
            'last_name' => 'Customer',
            'customer_type' => 'Normal',
        ]);
        $cleanResponse = $this->actingAs($this->manager)->delete(route('customers.destroy', $cleanCustomer));
        $cleanResponse->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['id' => $cleanCustomer->id]);
    }

    public function test_soa_balance_brought_forward_and_auto_mark_paid_on_zero_balance(): void
    {
        $customer = Customer::create([
            'first_name' => 'SOA',
            'last_name' => 'Account',
            'customer_type' => 'Company',
        ]);
        $account = CreditAccount::create([
            'customer_id' => $customer->id,
            'credit_limit' => 20000,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        // Prior period charge (Jan 15) = 3000
        CreditLedger::create([
            'credit_account_id' => $account->id,
            'transaction_type' => 'Charge',
            'amount' => 3000.00,
            'created_at' => Carbon::parse('2026-01-15 10:00:00'),
        ]);

        // Prior period payment (Jan 20) = 1000
        CreditLedger::create([
            'credit_account_id' => $account->id,
            'transaction_type' => 'Payment',
            'amount' => 1000.00,
            'created_at' => Carbon::parse('2026-01-20 10:00:00'),
        ]);
        // Previous balance prior to Feb 01 = 2000.00

        // Current period charge (Feb 05) = 1500
        CreditLedger::create([
            'credit_account_id' => $account->id,
            'transaction_type' => 'Charge',
            'amount' => 1500.00,
            'created_at' => Carbon::parse('2026-02-05 10:00:00'),
        ]);

        // Generate SOA for Feb 01 - Feb 28
        // Total due as of Feb 28 = (3000 + 1500) - 1000 = 3500.00
        $soaResponse = $this->actingAs($this->manager)->post(route('statements.store'), [
            'credit_account_id' => $account->id,
            'billing_period_start' => '2026-02-01',
            'billing_period_end' => '2026-02-28',
        ]);
        $soaResponse->assertSessionHasNoErrors();

        $soa = StatementOfAccount::latest()->first();
        $this->assertEquals(3500.00, (float)$soa->total_due);
        $this->assertFalse((bool)$soa->is_paid);

        // Inspect show page for Balance Brought Forward
        $showResponse = $this->actingAs($this->manager)->get(route('statements.show', $soa));
        $showResponse->assertStatus(200);
        $showResponse->assertSee("₱2,000.00"); // Prior balance brought forward

        // Now pay off the entire outstanding balance of 3500.00
        $payResponse = $this->actingAs($this->cashier)->post(route('payments.store', $account), [
            'amount' => 3500.00,
        ]);
        $payResponse->assertSessionHasNoErrors();

        // Verify SOA auto-marked as paid
        $soa->refresh();
        $this->assertTrue((bool)$soa->is_paid);
    }
}
