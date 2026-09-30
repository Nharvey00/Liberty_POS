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

class SecurityRemediationAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier1;
    protected User $cashier2;
    protected User $manager;
    protected Product $cylinderProduct;
    protected Customer $customer;
    protected CreditAccount $creditAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Level 1']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Level 2']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Level 3']);

        $this->cashier1 = User::factory()->create([
            'first_name' => 'Ana',
            'last_name' => 'Cashier',
            'role_id' => 1,
        ]);

        $this->cashier2 = User::factory()->create([
            'first_name' => 'Ben',
            'last_name' => 'Cashier',
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Mark',
            'last_name' => 'Manager',
            'role_id' => 2,
        ]);

        $this->cylinderProduct = Product::create([
            'name' => '11kg LPG Refill',
            'sku' => 'LPG-11-AUDIT',
            'category' => 'Cylinder',
            'price' => 950.00,
            'new_cylinder_price' => 2400.00,
            'standard_capacity_kg' => 11.0,
            'stock_quantity' => 20,
            'empty_quantity' => 10,
        ]);

        $this->customer = Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'customer_type' => 'Individual',
            'phone' => '09123456789',
        ]);

        $this->creditAccount = CreditAccount::create([
            'customer_id' => $this->customer->id,
            'credit_limit' => 10000.00,
            'is_active' => true,
        ]);
    }

    /**
     * Fix 1: Eliminate Double-Void Concurrency Races
     */
    public function test_double_void_aborts_when_order_already_voided(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->cashier1->id,
            'total_amount' => 950.00,
            'payment_method' => 'Credit',
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->cylinderProduct->id,
            'quantity' => 1,
            'is_swap' => true,
            'subtotal' => 950.00,
        ]);

        CreditLedger::create([
            'credit_account_id' => $this->creditAccount->id,
            'order_id' => $order->id,
            'transaction_type' => 'Charge',
            'amount' => 950.00,
        ]);

        // First void succeeds
        $firstVoid = $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Customer changed mind immediately',
        ]);
        $firstVoid->assertRedirect(route('orders.show', $order));
        $firstVoid->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('voided', $order->status);

        // Attempting to void again redirects with error and does NOT duplicate reversals
        $secondVoid = $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Duplicate void attempt simulation',
        ]);
        $secondVoid->assertRedirect(route('orders.show', $order));
        $secondVoid->assertSessionHas('error');

        // Verify credit ledger has exactly 1 Charge and 1 Payment reversal (not 2)
        $reversalPayments = CreditLedger::where('order_id', $order->id)
            ->where('transaction_type', 'Payment')
            ->count();
        $this->assertEquals(1, $reversalPayments);
    }

    /**
     * Fix 2: Unique Invoice Number Generation Post-Insertion
     */
    public function test_atomic_invoice_sequence_generation_guarantees_uniqueness(): void
    {
        $response1 = $this->actingAs($this->cashier1)->post(route('pos.store'), [
            'payment_method' => 'Cash',
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);
        $response1->assertSessionHasNoErrors();

        $response2 = $this->actingAs($this->cashier2)->post(route('pos.store'), [
            'payment_method' => 'Cash',
            'items' => [
                [
                    'product_id' => $this->cylinderProduct->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);
        $response2->assertSessionHasNoErrors();

        $orders = Order::whereNotNull('invoice_number')->get();
        $this->assertCount(2, $orders);
        $this->assertNotEquals($orders[0]->invoice_number, $orders[1]->invoice_number);
        $this->assertStringStartsWith('INV-' . date('Y') . '-', $orders[0]->invoice_number);
        $this->assertStringStartsWith('INV-' . date('Y') . '-', $orders[1]->invoice_number);
    }

    /**
     * Fix 3: Cashier Payment Redirect Crash (Prevents 403)
     */
    public function test_cashier_payment_redirects_to_pos_while_manager_redirects_to_account(): void
    {
        // 1. Cashier records payment -> Redirects to pos.create with success flash (NOT 403)
        $cashierPayment = $this->actingAs($this->cashier1)->post(route('payments.store', $this->creditAccount), [
            'amount' => 500.00,
            'notes' => 'Counter collection by cashier',
        ]);
        $cashierPayment->assertRedirect(route('pos.create'));
        $cashierPayment->assertSessionHas('success');

        // 2. Manager records payment -> Redirects to credit-accounts.show
        $managerPayment = $this->actingAs($this->manager)->post(route('payments.store', $this->creditAccount), [
            'amount' => 300.00,
            'notes' => 'Management desk collection',
        ]);
        $managerPayment->assertRedirect(route('credit-accounts.show', $this->creditAccount));
        $managerPayment->assertSessionHas('success');
    }

    /**
     * Fix 4: Retain Advance Deposits on SOA
     */
    public function test_soa_retains_negative_previous_balance_as_advance_credit(): void
    {
        // Customer made an advance payment of ₱1000 before billing period
        CreditLedger::create([
            'credit_account_id' => $this->creditAccount->id,
            'transaction_type' => 'Payment',
            'amount' => 1000.00,
            'created_at' => Carbon::parse('2026-01-10 10:00:00'),
        ]);

        // Customer took ₱1500 of charges during billing period
        CreditLedger::create([
            'credit_account_id' => $this->creditAccount->id,
            'transaction_type' => 'Charge',
            'amount' => 1500.00,
            'created_at' => Carbon::parse('2026-02-15 14:00:00'),
        ]);

        // Generate Statement for February
        $statement = StatementOfAccount::create([
            'credit_account_id' => $this->creditAccount->id,
            'billing_period_start' => '2026-02-01',
            'billing_period_end' => '2026-02-28',
            'total_due' => 500.00, // 1500 charges - 1000 advance payment
            'is_paid' => false,
        ]);

        $viewResponse = $this->actingAs($this->manager)->get(route('statements.show', $statement));
        $viewResponse->assertStatus(200);

        // Previous balance must reflect advance credit and not be clamped to 0
        $viewResponse->assertSee('-₱1,000.00');
        $viewResponse->assertSee('Advance Credit');
        $viewResponse->assertSee('₱500.00'); // Total Outstanding Balance
    }

    /**
     * Fix 5: Prevent Receipt Enumeration
     */
    public function test_receipt_enumeration_blocked_for_unauthorized_cashiers(): void
    {
        // Order placed by Cashier 1
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->cashier1->id,
            'total_amount' => 950.00,
            'payment_method' => 'Cash',
            'invoice_number' => 'INV-2026-99999',
            'status' => 'completed',
        ]);

        // Cashier 1 can view own receipt
        $ownReceipt = $this->actingAs($this->cashier1)->get(route('pos.show', $order));
        $ownReceipt->assertStatus(200);

        // Cashier 2 CANNOT view Cashier 1's receipt -> 403 Forbidden
        $otherReceipt = $this->actingAs($this->cashier2)->get(route('pos.show', $order));
        $otherReceipt->assertStatus(403);

        // Manager CAN view any receipt -> 200 OK
        $managerReceipt = $this->actingAs($this->manager)->get(route('pos.show', $order));
        $managerReceipt->assertStatus(200);
    }

    /**
     * Final Audit: Product deletion blocked if physical stock remains
     */
    public function test_product_deletion_blocked_if_physical_stock_or_empty_shells_remain(): void
    {
        $product = Product::create([
            'name' => 'Ghost Stock Cylinder',
            'sku' => 'GHOST-01',
            'category' => 'Cylinder',
            'price' => 500.00,
            'stock_quantity' => 5,
            'empty_quantity' => 0,
        ]);

        $response = $this->actingAs($this->manager)->delete(route('products.destroy', $product));
        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    /**
     * Final Audit: Customer deletion blocked if advance credit balance exists
     */
    public function test_customer_deletion_blocked_if_advance_credit_balance_exists(): void
    {
        $advCustomer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'customer_type' => 'Individual',
        ]);

        $advAccount = CreditAccount::create([
            'customer_id' => $advCustomer->id,
            'credit_limit' => 5000.00,
            'is_active' => true,
        ]);

        CreditLedger::create([
            'credit_account_id' => $advAccount->id,
            'transaction_type' => 'Payment',
            'amount' => 500.00,
        ]);

        $response = $this->actingAs($this->manager)->delete(route('customers.destroy', $advCustomer));
        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('customers', ['id' => $advCustomer->id]);
    }

    /**
     * Final Audit: Owner self-downgrade and sole owner deletion blocked
     */
    public function test_owner_self_downgrade_and_sole_owner_deletion_blocked(): void
    {
        $owner = User::factory()->create([
            'first_name' => 'Sole',
            'last_name' => 'Owner',
            'role_id' => 3,
        ]);

        // Attempting to downgrade own role
        $downgradeResponse = $this->actingAs($owner)->put(route('users.update', $owner), [
            'first_name' => 'Sole',
            'last_name' => 'Owner',
            'email' => $owner->email,
            'role_id' => 1, // Cashier
        ]);
        $downgradeResponse->assertSessionHasErrors();
        $owner->refresh();
        $this->assertEquals(3, $owner->role_id);

        // Attempting to delete own account
        $deleteSelfResponse = $this->actingAs($owner)->delete(route('users.destroy', $owner));
        $deleteSelfResponse->assertSessionHasErrors();
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    /**
     * Final Audit: Stock out rejects zero-quantity removal
     */
    public function test_stock_out_rejects_zero_quantity_removal(): void
    {
        $response = $this->actingAs($this->manager)->post(route('stock-outs.store'), [
            'product_id' => $this->cylinderProduct->id,
            'quantity_removed' => 0,
            'empty_quantity_removed' => 0,
            'reason' => 'Defect',
        ]);
        $response->assertSessionHasErrors('quantity_removed');
    }

    /**
     * Household customer classification validation test
     */
    public function test_household_customer_type_passes_validation_on_create_and_update(): void
    {
        // 1. Create with Household
        $createResponse = $this->actingAs($this->manager)->post(route('customers.store'), [
            'first_name' => 'Aling',
            'last_name' => 'Nena',
            'customer_type' => 'Household',
            'phone' => '09991234567',
        ]);
        $createResponse->assertSessionHasNoErrors();
        $createResponse->assertRedirect(route('customers.index'));

        $customer = Customer::where('first_name', 'Aling')->where('last_name', 'Nena')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Household', $customer->customer_type);

        // 2. Update with Household
        $updateResponse = $this->actingAs($this->manager)->put(route('customers.update', $customer), [
            'first_name' => 'Aling',
            'last_name' => 'Nena',
            'customer_type' => 'Household',
            'phone' => '09997654321',
        ]);
        $updateResponse->assertSessionHasNoErrors();
        $updateResponse->assertRedirect(route('customers.index'));
        $customer->refresh();
        $this->assertEquals('Household', $customer->customer_type);
        $this->assertEquals('09997654321', $customer->phone);
    }
}
