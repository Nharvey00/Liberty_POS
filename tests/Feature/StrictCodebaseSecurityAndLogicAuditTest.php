<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\TankLoan;
use App\Models\TankLoanReturn;
use App\Models\User;
use App\Services\AuditTrailService;
use Database\Seeders\ComprehensiveLpgDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StrictCodebaseSecurityAndLogicAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $otherCashier;
    protected User $manager;
    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Level 1']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Level 2']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Level 3']);

        $this->cashier = User::factory()->create([
            'first_name' => 'Cashier',
            'last_name' => 'One',
            'email' => 'cashier1.audit@test.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);

        $this->otherCashier = User::factory()->create([
            'first_name' => 'Cashier',
            'last_name' => 'Two',
            'email' => 'cashier2.audit@test.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Store',
            'last_name' => 'Manager',
            'email' => 'manager.audit@test.com',
            'password' => Hash::make('password'),
            'role_id' => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'Owner',
            'email' => 'owner.audit@test.com',
            'password' => Hash::make('password'),
            'role_id' => 3,
        ]);
    }

    // =========================================================================
    // PILLAR 1: CREDENTIAL LEAKS, PRODUCTION GUARDS & DATA EXPOSURE
    // =========================================================================

    public function test_pillar_1_production_seeder_guard_prevents_demo_data_injection_in_production(): void
    {
        $originalEnv = $this->app['env'];

        try {
            $this->app['env'] = 'production';
            $this->assertTrue(app()->environment('production'));

            app(DatabaseSeeder::class)->run();
            app(ComprehensiveLpgDemoSeeder::class)->run();

            // Foundational roles and users are allowed, but fake demo products, customers, orders, and loans MUST NOT be seeded
            $this->assertEquals(0, Product::withTrashed()->count(), 'Demo products must never be seeded in production.');
            $this->assertEquals(0, Customer::withTrashed()->count(), 'Demo customers must never be seeded in production.');
            $this->assertEquals(0, Order::count(), 'Demo orders must never be seeded in production.');
            $this->assertEquals(0, TankLoan::count(), 'Demo tank loans must never be seeded in production.');
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    public function test_pillar_1_audit_trail_service_redacts_sensitive_credential_fields(): void
    {
        $this->actingAs($this->owner);

        $log = AuditTrailService::record([
            'module'        => 'users',
            'action'        => 'user.updated',
            'entity_type'   => 'User',
            'entity_id'     => $this->cashier->id,
            'before_values' => [
                'email'          => 'cashier1.audit@test.com',
                'password'       => 'SuperSecretPlaintext123!',
                'remember_token' => 'secret-token-abc',
            ],
            'after_values'  => [
                'email'                 => 'cashier1.audit@test.com',
                'password'              => '$2y$12$hashedpasswordvalue',
                'password_confirmation' => 'SuperSecretPlaintext123!',
                'current_password'      => 'OldSecret123!',
            ],
            'reason'        => 'Security redaction test',
        ]);

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->before_values);
        $this->assertArrayNotHasKey('remember_token', $log->before_values);
        $this->assertArrayNotHasKey('password', $log->after_values);
        $this->assertArrayNotHasKey('password_confirmation', $log->after_values);
        $this->assertArrayNotHasKey('current_password', $log->after_values);
        $this->assertEquals('cashier1.audit@test.com', $log->before_values['email']);
    }

    // =========================================================================
    // PILLAR 2: RBAC, AUTHORIZATION & SELF-DELETION PROTECTION
    // =========================================================================

    public function test_pillar_2_cashier_and_manager_blocked_from_voiding_stock_in_and_stock_out(): void
    {
        $product = Product::create([
            'name' => 'Solane 11kg QR',
            'sku' => 'AUDIT-SOL-11',
            'price' => 980,
            'cost_price' => 860,
            'stock_quantity' => 20,
            'empty_quantity' => 10,
            'reorder_level' => 5,
            'standard_capacity_kg' => 11.00,
        ]);

        $stockIn = StockIn::create([
            'product_id' => $product->id,
            'user_id' => $this->manager->id,
            'approved_by' => $this->manager->id,
            'supplier_name' => 'Isla LPG Corp.',
            'quantity_received' => 5,
            'empty_received_qty' => 0,
            'empty_shells_received' => 0,
            'empty_returned_qty' => 0,
            'unit_cost' => 860,
            'remarks' => 'Test delivery',
        ]);

        $stockOut = StockOut::create([
            'product_id' => $product->id,
            'user_id' => $this->manager->id,
            'approved_by' => $this->manager->id,
            'movement_category' => 'manual_adjustment',
            'quantity_removed' => 2,
            'empty_quantity_removed' => 0,
            'reason' => 'Damaged / Leaking Return',
        ]);

        // Level 1 Cashier MUST receive 403
        $this->actingAs($this->cashier)
            ->post(route('stock-ins.void', $stockIn), ['void_reason' => 'Cashier unauthorized attempt'])
            ->assertForbidden();

        $this->actingAs($this->cashier)
            ->post(route('stock-outs.void', $stockOut), ['void_reason' => 'Cashier unauthorized attempt'])
            ->assertForbidden();

        // Level 2 Manager MUST ALSO receive 403 (strictly Level 3 Admin Owner only)
        $this->actingAs($this->manager)
            ->post(route('stock-ins.void', $stockIn), ['void_reason' => 'Manager unauthorized attempt'])
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->post(route('stock-outs.void', $stockOut), ['void_reason' => 'Manager unauthorized attempt'])
            ->assertForbidden();

        $this->assertFalse($stockIn->fresh()->is_voided);
        $this->assertFalse($stockOut->fresh()->is_voided);

        // Level 3 Admin Owner succeeds
        $this->actingAs($this->owner)
            ->post(route('stock-ins.void', $stockIn), ['void_reason' => 'Owner authorized void'])
            ->assertRedirect(route('stock-ins.index'));

        $this->actingAs($this->owner)
            ->post(route('stock-outs.void', $stockOut), ['void_reason' => 'Owner authorized void'])
            ->assertRedirect(route('stock-outs.index'));

        $this->assertTrue($stockIn->fresh()->is_voided);
        $this->assertTrue($stockOut->fresh()->is_voided);
    }

    public function test_pillar_2_cashier_blocked_from_admin_and_manager_routes_and_idor(): void
    {
        $customer = Customer::create([
            'first_name' => 'Pedro',
            'last_name' => 'Penduko',
            'customer_type' => 'Regular',
            'phone' => '09170001111',
        ]);

        $product = Product::create([
            'name' => 'Prycegas 11kg',
            'sku' => 'AUDIT-PRY-11',
            'price' => 950,
            'cost_price' => 830,
            'stock_quantity' => 15,
            'empty_quantity' => 5,
            'reorder_level' => 5,
            'standard_capacity_kg' => 11.00,
        ]);

        $loan = TankLoan::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'tank_condition_out' => 'filled',
            'quantity_borrowed' => 1,
            'quantity_returned' => 0,
            'status' => 'borrowed',
            'created_by' => $this->manager->id,
            'borrowed_at' => now(),
        ]);

        $otherOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $this->otherCashier->id,
            'invoice_number' => 'INV-2026-99999',
            'total_amount' => 950,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        $this->actingAs($this->cashier);

        // Blocked from Loaned Tanks (Huram), Stock Outs, Audit Logs, User Management, Order Void, Customer/Product Deletion
        $this->get(route('tank-loans.index'))->assertForbidden();
        $this->get(route('tank-loans.create'))->assertForbidden();
        $this->post(route('tank-loans.store'), [])->assertForbidden();
        $this->get(route('tank-loans.customer', $customer))->assertForbidden();
        $this->post(route('tank-loans.return', $loan), [])->assertForbidden();
        $this->get(route('stock-outs.index'))->assertForbidden();
        $this->post(route('stock-outs.store'), [])->assertForbidden();
        $this->get(route('audit-logs.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
        $this->post(route('orders.void', $otherOrder), ['void_reason' => 'Attempt'])->assertForbidden();
        $this->delete(route('customers.destroy', $customer))->assertForbidden();
        $this->delete(route('products.destroy', $product))->assertForbidden();

        // IDOR check: Cashier 1 cannot view Cashier 2's order receipt
        $this->get(route('pos.show', $otherOrder))->assertForbidden();
        $this->get(route('orders.show', $otherOrder))->assertForbidden();
    }

    public function test_pillar_2_sole_owner_cannot_self_delete_via_profile_destroy(): void
    {
        $this->assertEquals(1, User::where('role_id', 3)->count());

        $response = $this->actingAs($this->owner)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrorsIn('userDeletion', 'password');
        $this->assertNull($this->owner->fresh()->deleted_at);
    }

    // =========================================================================
    // PILLAR 3: CONCURRENCY, OVER-RETURN & DOUBLE-VOID SAFEGUARDS
    // =========================================================================

    public function test_pillar_3_tank_loan_over_return_and_invalid_return_quantities_are_blocked(): void
    {
        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Bautista',
            'customer_type' => 'Regular',
        ]);

        $product = Product::create([
            'name' => 'Gasul 11kg Snap-On',
            'sku' => 'AUDIT-GAS-11',
            'price' => 970,
            'cost_price' => 850,
            'stock_quantity' => 20,
            'empty_quantity' => 10,
            'reorder_level' => 5,
            'standard_capacity_kg' => 11.00,
        ]);

        $this->actingAs($this->manager);

        // Borrow 3 filled tanks -> stock_quantity becomes 17
        $this->post(route('tank-loans.store'), [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'tank_condition_out' => 'filled',
            'quantity_borrowed' => 3,
            'notes' => 'Event loan',
        ])->assertRedirect(route('tank-loans.index'));

        $loan = TankLoan::firstOrFail();
        $this->assertEquals(17, $product->fresh()->stock_quantity);
        $this->assertEquals(10, $product->fresh()->empty_quantity);

        // Return 2 empty shells -> status becomes partially_returned, 1 remaining
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 2,
            'returned_as' => 'empty_shell',
            'notes' => 'Partial return of 2 shells',
        ])->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertEquals(2, $loan->quantity_returned);
        $this->assertEquals('partially_returned', $loan->status);
        $this->assertEquals(12, $product->fresh()->empty_quantity);

        // Attempt to over-return 2 more tanks when only 1 remains -> MUST FAIL
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 2,
            'returned_as' => 'empty_shell',
        ])->assertSessionHasErrors('quantity_returned');

        // Attempt to return 0 or -1 tanks -> MUST FAIL
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 0,
            'returned_as' => 'empty_shell',
        ])->assertSessionHasErrors('quantity_returned');

        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => -1,
            'returned_as' => 'empty_shell',
        ])->assertSessionHasErrors('quantity_returned');

        // Verify state is untouched after failed attempts
        $loan->refresh();
        $this->assertEquals(2, $loan->quantity_returned);
        $this->assertEquals(12, $product->fresh()->empty_quantity);

        // Return final 1 tank -> status becomes returned
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 1,
            'returned_as' => 'empty_shell',
        ])->assertSessionHasNoErrors();

        $loan->refresh();
        $this->assertEquals(3, $loan->quantity_returned);
        $this->assertEquals('returned', $loan->status);
        $this->assertEquals(13, $product->fresh()->empty_quantity);

        // Attempt to return 1 more tank on an already-returned loan -> MUST FAIL
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 1,
            'returned_as' => 'empty_shell',
        ])->assertSessionHasErrors('quantity_returned');
    }

    // =========================================================================
    // PILLAR 4: LPG BUSINESS LOGIC, REFERENTIAL INTEGRITY & DISCOUNT BOUNDARIES
    // =========================================================================

    public function test_pillar_4_customer_deletion_blocked_when_unpaid_utang_or_active_huram_tanks_exist(): void
    {
        $this->actingAs($this->manager);

        $product = Product::create([
            'name' => 'Solane 11kg Screw',
            'sku' => 'AUDIT-SOL-11S',
            'price' => 980,
            'cost_price' => 860,
            'stock_quantity' => 20,
            'empty_quantity' => 10,
            'reorder_level' => 5,
            'standard_capacity_kg' => 11.00,
        ]);

        // 1. Customer with unpaid credit (utang > 0)
        $utangCustomer = Customer::create([
            'first_name' => 'Ramon',
            'last_name' => 'Magsaysay',
            'customer_type' => 'Regular',
        ]);

        $creditAccount = CreditAccount::create([
            'customer_id' => $utangCustomer->id,
            'credit_limit' => 5000.00,
            'is_active' => true,
            'status' => 'approved',
        ]);

        \App\Models\CreditLedger::create([
            'credit_account_id' => $creditAccount->id,
            'transaction_type' => 'Charge',
            'amount' => 980.00,
            'user_id' => $this->cashier->id,
            'balance_before' => 0.00,
            'balance_after' => 980.00,
            'notes' => 'Unpaid POS Credit Sale',
        ]);

        $this->delete(route('customers.destroy', $utangCustomer))
            ->assertSessionHasErrors('customer');
        $this->assertNull($utangCustomer->fresh()->deleted_at, 'Customer with unpaid utang must not be deleted.');

        // 2. Customer with active unreturned Huram loaned tank
        $huramCustomer = Customer::create([
            'first_name' => 'Lorna',
            'last_name' => 'Tolentino',
            'customer_type' => 'Regular',
        ]);

        $this->post(route('tank-loans.store'), [
            'customer_id' => $huramCustomer->id,
            'product_id' => $product->id,
            'tank_condition_out' => 'filled',
            'quantity_borrowed' => 1,
        ])->assertRedirect(route('tank-loans.index'));

        $this->delete(route('customers.destroy', $huramCustomer))
            ->assertSessionHasErrors('customer');
        $this->assertNull($huramCustomer->fresh()->deleted_at, 'Customer with active Huram tanks must not be deleted.');

        // Return the tank -> customer deletion now succeeds
        $loan = TankLoan::where('customer_id', $huramCustomer->id)->firstOrFail();
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 1,
            'returned_as' => 'empty_shell',
        ])->assertSessionHasNoErrors();

        $this->delete(route('customers.destroy', $huramCustomer))
            ->assertRedirect(route('customers.index'));
        $this->assertNotNull($huramCustomer->fresh()->deleted_at, 'Customer should be soft-deleted once all Huram tanks are returned.');
    }

    public function test_pillar_4_product_archiving_blocked_when_active_unreturned_huram_tanks_exist(): void
    {
        $this->actingAs($this->manager);

        $customer = Customer::create([
            'first_name' => 'Nestor',
            'last_name' => 'Villanueva',
            'customer_type' => 'Tertiary',
        ]);

        $product = Product::create([
            'name' => 'Phoenix Super LPG 11kg',
            'sku' => 'AUDIT-PHX-11',
            'price' => 940,
            'cost_price' => 820,
            'stock_quantity' => 15,
            'empty_quantity' => 8,
            'reorder_level' => 5,
            'standard_capacity_kg' => 11.00,
        ]);

        $this->post(route('tank-loans.store'), [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'tank_condition_out' => 'empty_shell',
            'quantity_borrowed' => 2,
        ])->assertRedirect(route('tank-loans.index'));

        // Attempting to archive/delete product while 2 tanks are out on loan MUST fail
        $this->delete(route('products.destroy', $product))
            ->assertSessionHasErrors('product');
        $this->assertNull($product->fresh()->deleted_at, 'Product with active Huram loans must not be archived.');

        // Return the 2 shells -> archiving the product now succeeds
        $loan = TankLoan::where('product_id', $product->id)->firstOrFail();
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 2,
            'returned_as' => 'empty_shell',
        ])->assertSessionHasNoErrors();

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));
        $this->assertNotNull($product->fresh()->deleted_at, 'Product should be archived after all Huram loans are returned.');
    }

    public function test_pillar_4_discount_math_boundaries_and_stale_discount_amount_forced_to_zero_when_none(): void
    {
        $this->actingAs($this->cashier);

        $customer = Customer::create([
            'first_name' => 'walkin',
            'last_name' => 'buyer',
            'customer_type' => 'Regular',
        ]);

        $product = Product::create([
            'name' => 'Isla Gas 11kg POL',
            'sku' => 'AUDIT-ISL-11',
            'price' => 960.00,
            'cost_price' => 840.00,
            'stock_quantity' => 30,
            'empty_quantity' => 10,
            'reorder_level' => 5,
            'standard_capacity_kg' => 11.00,
        ]);

        // 1. Negative discount amount is rejected
        $this->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'regular',
            'discount_amount' => -50.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'is_swap' => true],
            ],
        ])->assertSessionHasErrors('discount_amount');

        // 2. Senior/PWD discount rate > 100% is rejected
        $this->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'senior',
            'discount_rate' => 125.00,
            'discount_amount' => 100.00,
            'discount_reference_name' => 'Lola Basyang',
            'discount_reference_id' => 'OSCA-999',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'is_swap' => true],
            ],
        ])->assertSessionHasErrors('discount_rate');

        // 3. Fixed Peso discount exceeding subtotal (₱1,200 > ₱960) is rejected
        $this->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'regular',
            'discount_amount' => 1200.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'is_swap' => true],
            ],
        ])->assertSessionHasErrors('discount_amount');

        // 4. Stale discount_amount sent when discount_type === 'none' is forced to ₱0.00
        $this->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'none',
            'discount_amount' => 150.00,
            'discount_reference_name' => 'Stale Ref',
            'discount_reference_id' => 'STALE-123',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1, 'is_swap' => true],
            ],
        ])->assertSessionHasNoErrors();

        $order = Order::latest('id')->firstOrFail();
        $this->assertNull($order->discount_type);
        $this->assertEquals(0.00, (float) $order->discount_amount, 'Stale discount_amount must be forced to 0.00 when discount_type is none.');
        $this->assertEquals(960.00, (float) $order->total_amount, 'Order total must equal full subtotal when discount_type is none.');
        $this->assertNull($order->discount_reference_name);
        $this->assertNull($order->discount_reference_id);
    }

    public function test_pillar_4_huram_tank_return_uses_huram_ret_reference_and_does_not_inflate_supplier_deliveries_report(): void
    {
        $this->actingAs($this->owner);

        $customer = Customer::create([
            'first_name' => 'Arturo',
            'last_name' => 'Gomez',
            'customer_type' => 'Industrial',
        ]);

        $product = Product::create([
            'name' => 'Solane 50kg Industrial',
            'sku' => 'AUDIT-SOL-50',
            'price' => 4200.00,
            'cost_price' => 3750.00,
            'stock_quantity' => 10,
            'empty_quantity' => 5,
            'reorder_level' => 4,
            'standard_capacity_kg' => 50.00,
        ]);

        // 1. Log a genuine supplier delivery of 10 filled tanks
        $this->post(route('stock-ins.store'), [
            'supplier_name' => 'Isla LPG Corp.',
            'remarks' => 'Genuine supplier delivery',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity_received' => 10,
                    'empty_received_qty' => 0,
                    'empty_returned_qty' => 0,
                    'unit_cost' => 3750.00,
                ],
            ],
        ])->assertRedirect(route('stock-ins.index'));

        // 2. Loan 2 filled tanks to customer and return them as filled tanks
        $this->post(route('tank-loans.store'), [
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'tank_condition_out' => 'filled',
            'quantity_borrowed' => 2,
        ])->assertRedirect(route('tank-loans.index'));

        $loan = TankLoan::firstOrFail();
        $this->post(route('tank-loans.return', $loan), [
            'quantity_returned' => 2,
            'returned_as' => 'filled',
            'notes' => 'Returned unused filled tanks',
        ])->assertSessionHasNoErrors();

        $returnRecord = TankLoanReturn::firstOrFail();
        $returnStockIn = StockIn::findOrFail($returnRecord->stock_in_id);
        $this->assertStringStartsWith('HURAM-RET-', (string) $returnStockIn->reference_no);

        // 3. Check Inventory Report: period_in (Supplier Deliveries) must be 10, NOT 12
        $response = $this->get(route('reports.inventory', [
            'from_date' => now()->subDay()->toDateString(),
            'to_date' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk();
        $reportedProducts = $response->viewData('products');
        $reportedProduct = $reportedProducts->firstWhere('id', $product->id);
        $this->assertNotNull($reportedProduct);
        $this->assertEquals(10, (int) $reportedProduct->period_in, 'Customer Huram returns must not inflate supplier delivery totals.');
    }

    // =========================================================================
    // PILLAR 5: UNIFIED SUB-NAVIGATION TABS & STRICT OWNER-ONLY DELETED RECORDS
    // =========================================================================

    public function test_pillar_5_inventory_hub_5_tabs_and_strict_owner_only_deleted_records_access(): void
    {
        $product = Product::create([
            'name' => 'Archived Cylinder 11kg',
            'sku' => 'ARCH-11',
            'price' => 950,
            'cost_price' => 830,
            'stock_quantity' => 5,
            'empty_quantity' => 2,
            'reorder_level' => 2,
            'standard_capacity_kg' => 11.00,
        ]);
        $product->delete();

        $customer = Customer::create([
            'first_name' => 'Archived',
            'last_name' => 'Client',
            'customer_type' => 'Regular',
        ]);
        $customer->delete();

        $inventoryHubRoutes = [
            route('products.index'),
            route('stock-ins.index'),
            route('stock-outs.index'),
            route('inventory-movements.index'),
        ];

        // 1. Level 3 Admin Owner sees all 5 tabs across all 4 Inventory Hub pages and can access/restore deleted records
        $this->actingAs($this->owner);
        foreach ($inventoryHubRoutes as $url) {
            $res = $this->get($url);
            $res->assertOk();
            $res->assertSee('Products Catalog');
            $res->assertSee('Stock In History');
            $res->assertSee('Stock Out History');
            $res->assertSee('Inventory Ledger');
            $res->assertSee('Deleted Products');
        }

        $this->get(route('products.index', ['status' => 'deleted']))
            ->assertOk()
            ->assertSee('Archived Cylinder 11kg');

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Active Customers')
            ->assertSee('Deleted Customers');

        $this->get(route('customers.index', ['status' => 'deleted']))
            ->assertOk()
            ->assertSee('Archived Client');

        // 2. Level 2 Manager sees the first 4 tabs, does NOT see Deleted Products or Deleted Customers, and gets 403 on ?status=deleted & restore
        $this->actingAs($this->manager);
        foreach ($inventoryHubRoutes as $url) {
            $res = $this->get($url);
            $res->assertOk();
            $res->assertSee('Products Catalog');
            $res->assertSee('Stock In History');
            $res->assertSee('Stock Out History');
            $res->assertSee('Inventory Ledger');
            $res->assertDontSee('Deleted Products');
        }

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Active Customers')
            ->assertDontSee('Deleted Customers');

        $this->get(route('products.index', ['status' => 'deleted']))->assertForbidden();
        $this->get(route('customers.index', ['status' => 'deleted']))->assertForbidden();
        $this->post(route('products.restore', $product->id))->assertForbidden();
        $this->post(route('customers.restore', $customer->id))->assertForbidden();

        // Owner can restore both
        $this->actingAs($this->owner);
        $this->post(route('products.restore', $product->id))->assertRedirect(route('products.index', ['status' => 'deleted']));
        $this->post(route('customers.restore', $customer->id))->assertRedirect(route('customers.index', ['status' => 'deleted']));
        $this->assertFalse($product->fresh()->trashed());
        $this->assertFalse($customer->fresh()->trashed());
    }

    public function test_pillar_5_credit_receivables_and_reports_analytics_unified_sub_navigation(): void
    {
        $this->actingAs($this->manager);

        // Credit & Receivables sibling pages
        $creditUrls = [
            route('credit-accounts.index'),
            route('statements.index'),
            route('tank-loans.index'),
        ];

        foreach ($creditUrls as $url) {
            $res = $this->get($url);
            $res->assertOk();
            $res->assertSee('Credit Accounts (Utang)');
            $res->assertSee('Statements of Account');
            $res->assertSee('Loaned Tanks (Huram)');
        }

        // Reports & Analytics + Audit Trail sibling pages
        $reportUrls = [
            route('reports.index'),
            route('reports.sales'),
            route('reports.inventory'),
            route('reports.utang'),
            route('reports.discounts'),
            route('audit-logs.index'),
        ];

        foreach ($reportUrls as $url) {
            $res = $this->get($url);
            $res->assertOk();
            $res->assertSee('Overview Hub');
            $res->assertSee('Sales Report');
            $res->assertSee('Inventory Report');
            $res->assertSee('Utang (Credit) Report');
            $res->assertSee('Discounts Summary');
            $res->assertSee('Audit Trail');
        }
    }
}
