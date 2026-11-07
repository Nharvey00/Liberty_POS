<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\StatementOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullStackAuditRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier1;
    protected User $cashier2;
    protected User $manager;
    protected Product $cylinderProduct;
    protected Customer $customerA;
    protected Customer $customerB;
    protected CreditAccount $accountA;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Level 1']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Level 2']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Level 3']);

        $this->cashier1 = User::factory()->create([
            'first_name' => 'Rosa',
            'last_name' => 'CashierOne',
            'role_id' => 1,
        ]);

        $this->cashier2 = User::factory()->create([
            'first_name' => 'Pedro',
            'last_name' => 'CashierTwo',
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Elena',
            'last_name' => 'Manager',
            'role_id' => 2,
        ]);

        $this->cylinderProduct = Product::create([
            'name' => '11kg Solane Refill',
            'sku' => 'SOL-11KG',
            'price' => 980.00,
            'new_cylinder_price' => 2200.00,
            'standard_capacity_kg' => 11.0,
            'stock_quantity' => 15,
            'empty_quantity' => 5,
        ]);

        $this->customerA = Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'UniqueLastNameAlpha',
            'business_name' => 'Alpha Eatery',
            'customer_type' => 'Commercial',
            'phone' => '09171112222',
        ]);

        $this->customerB = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'UniqueLastNameBeta',
            'business_name' => 'Beta Bakery',
            'customer_type' => 'Commercial',
            'phone' => '09173334444',
        ]);

        $this->accountA = CreditAccount::create([
            'customer_id' => $this->customerA->id,
            'credit_limit' => 10000.00,
            'total_credit' => 980.00,
            'total_paid' => 0.00,
            'remaining_balance' => 980.00,
            'is_active' => true,
        ]);
    }

    public function test_cb3_payment_create_cancel_button_routes_cashier_to_pos_and_manager_to_credit_account(): void
    {
        $cashierResponse = $this->actingAs($this->cashier1)
            ->get(route('payments.create', $this->accountA));

        $cashierResponse->assertOk();
        $cashierResponse->assertSee('href="' . route('pos.create') . '"', false);
        $cashierResponse->assertDontSee('href="' . route('credit-accounts.show', $this->accountA) . '"', false);

        $managerResponse = $this->actingAs($this->manager)
            ->get(route('payments.create', $this->accountA));

        $managerResponse->assertOk();
        $managerResponse->assertSee('href="' . route('credit-accounts.show', $this->accountA) . '"', false);
    }

    public function test_cb4_product_capacity_validation_rejects_zero_and_prevents_division_by_zero(): void
    {
        $storeResponse = $this->actingAs($this->manager)->post(route('products.store'), [
            'name' => 'Zero Capacity Tank',
            'price' => 500,
            'standard_capacity_kg' => 0,
            'stock_quantity' => 10,
            'empty_quantity' => 0,
        ]);
        $storeResponse->assertSessionHasErrors('standard_capacity_kg');

        $updateResponse = $this->actingAs($this->manager)->put(route('products.update', $this->cylinderProduct), [
            'name' => $this->cylinderProduct->name,
            'price' => 980,
            'standard_capacity_kg' => 0,
        ]);
        $updateResponse->assertSessionHasErrors('standard_capacity_kg');

        // Verify defensive isAccessory() guard if a legacy record has 0 capacity
        $legacyZeroProduct = new Product([
            'name' => 'Legacy Zero',
            'price' => 500,
            'standard_capacity_kg' => 0,
        ]);
        $this->assertTrue($legacyZeroProduct->isAccessory());
    }

    public function test_cb5_customer_discount_type_validation_accepts_promo_and_rejects_custom(): void
    {
        $invalidResponse = $this->actingAs($this->manager)->post(route('customers.store'), [
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'customer_type' => 'Household',
            'default_discount_type' => 'custom',
            'default_discount_percentage' => 10,
        ]);
        $invalidResponse->assertSessionHasErrors('default_discount_type');

        $validResponse = $this->actingAs($this->manager)->post(route('customers.store'), [
            'first_name' => 'Promo',
            'last_name' => 'Customer',
            'customer_type' => 'Household',
            'default_discount_type' => 'promo',
            'default_discount_percentage' => 10,
        ]);
        $validResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', [
            'first_name' => 'Promo',
            'default_discount_type' => 'promo',
        ]);
    }

    public function test_cb6_and_cb8_cashier_sales_and_order_search_api_properly_group_orwhere_and_scope_to_cashier(): void
    {
        $orderCashier1 = Order::create([
            'user_id' => $this->cashier1->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-20261015-0001',
            'total_amount' => 980.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        $orderCashier2 = Order::create([
            'user_id' => $this->cashier2->id,
            'customer_id' => $this->customerB->id,
            'invoice_number' => 'INV-20261015-0002',
            'total_amount' => 980.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        // Cashier 1 searching for Customer B's last_name on My Sales must NOT leak Cashier 2's order
        $salesResponse = $this->actingAs($this->cashier1)
            ->get(route('cashier.sales', ['search' => 'UniqueLastNameBeta']));
        $salesResponse->assertOk();
        $salesResponse->assertDontSee('INV-20261015-0002');

        // Cashier 1 calling /api/orders/search must only see their own order
        $apiCashier1 = $this->actingAs($this->cashier1)
            ->getJson(route('api.orders.search', ['query' => 'INV-20261015']));
        $apiCashier1->assertOk();
        $returnedOrderIds = collect($apiCashier1->json())->pluck('id')->all();
        $this->assertContains($orderCashier1->id, $returnedOrderIds);
        $this->assertNotContains($orderCashier2->id, $returnedOrderIds);

        // Manager calling /api/orders/search sees both orders
        $apiManager = $this->actingAs($this->manager)
            ->getJson(route('api.orders.search', ['query' => 'INV-20261015']));
        $apiManager->assertOk();
        $managerOrderIds = collect($apiManager->json())->pluck('id')->all();
        $this->assertContains($orderCashier1->id, $managerOrderIds);
        $this->assertContains($orderCashier2->id, $managerOrderIds);
    }

    public function test_cb7_order_item_is_swap_boolean_string_persists_when_toggled_to_false(): void
    {
        $order = Order::create([
            'user_id' => $this->cashier1->id,
            'customer_id' => $this->customerA->id,
            'invoice_number' => 'INV-20261015-0099',
            'total_amount' => 980.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->cylinderProduct->id,
            'quantity' => 1,
            'price' => 980.00,
            'subtotal' => 980.00,
            'is_swap' => true,
        ]);

        $this->assertTrue($item->fresh()->is_swap);

        $item->is_swap = false;
        $item->save();

        $this->assertFalse($item->fresh()->is_swap);
    }

    public function test_lg8_statement_generation_prevents_duplicates_and_records_audit_trail(): void
    {
        CreditLedger::create([
            'credit_account_id' => $this->accountA->id,
            'user_id' => $this->cashier1->id,
            'transaction_type' => 'Charge',
            'amount' => 980.00,
            'balance_before' => 0.00,
            'balance_after' => 980.00,
            'notes' => 'Order #INV-20261015-0010 (Credit)',
        ]);

        $startDate = now()->subDays(7)->toDateString();
        $endDate = now()->toDateString();

        $firstResponse = $this->actingAs($this->manager)->post(route('statements.store'), [
            'credit_account_id' => $this->accountA->id,
            'billing_period_start' => $startDate,
            'billing_period_end' => $endDate,
        ]);
        $statement = StatementOfAccount::first();
        $this->assertNotNull($statement);
        $firstResponse->assertRedirect(route('statements.show', $statement));

        // Duplicate submission for the exact same period should redirect to existing SOA instead of creating a second one
        $duplicateResponse = $this->actingAs($this->manager)->post(route('statements.store'), [
            'credit_account_id' => $this->accountA->id,
            'billing_period_start' => $startDate,
            'billing_period_end' => $endDate,
        ]);
        $duplicateResponse->assertRedirect(route('statements.show', $statement));
        $duplicateResponse->assertSessionHas('success');
        $this->assertEquals(1, StatementOfAccount::count());

        // Toggle statement paid status and verify AuditLog records both generation and status update
        $this->actingAs($this->manager)->patch(route('statements.update', $statement));
        $this->assertTrue($statement->fresh()->is_paid);

        $this->assertDatabaseHas('audit_logs', [
            'module' => 'statements',
            'action' => 'statement.generated',
            'entity_id' => (string) $statement->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'statements',
            'action' => 'statement.marked_paid',
            'entity_id' => (string) $statement->id,
        ]);

        // Verify AuditLogController category=credit includes statements
        $auditResponse = $this->actingAs($this->manager)
            ->get(route('audit-logs.index', ['category' => 'credit', 'search' => 'Statement']));
        $auditResponse->assertOk();
        $auditResponse->assertSee('Statement of Account Generated');
    }
}
