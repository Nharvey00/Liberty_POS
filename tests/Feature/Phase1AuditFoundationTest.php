<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class Phase1AuditFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $manager;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Cashier']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Manager']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Owner']);

        $this->cashier = User::factory()->create([
            'first_name' => 'Juan',
            'last_name'  => 'Cashier',
            'role_id'    => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Maria',
            'last_name'  => 'Manager',
            'role_id'    => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Pedro',
            'last_name'  => 'Owner',
            'role_id'    => 3,
        ]);

        AuditTrailService::resetCorrelationId();
    }

    public function test_audit_trail_service_captures_actor_snapshot_and_redacts_sensitive_fields(): void
    {
        $this->actingAs($this->manager);

        $log = AuditTrailService::record([
            'module'                => 'users',
            'action'                => 'user.updated',
            'entity_type'           => 'User',
            'entity_id'             => $this->cashier->id,
            'transaction_reference' => 'USR-' . $this->cashier->id,
            'before_values'         => [
                'email'          => 'old@example.com',
                'password'       => 'secret_hash_old',
                'remember_token' => 'tok_123',
            ],
            'after_values'          => [
                'email'                 => 'new@example.com',
                'password'              => 'secret_hash_new',
                'password_confirmation' => 'secret_hash_new',
            ],
            'reason'                => 'Updated staff email address',
        ]);

        $this->assertNotNull($log->event_uuid);
        $this->assertNotNull($log->correlation_id);
        $this->assertEquals($this->manager->id, $log->actor_user_id);
        $this->assertEquals('Maria Manager', $log->actor_name_snapshot);
        $this->assertEquals('Manager', $log->actor_role_snapshot);
        $this->assertEquals('completed', $log->status);

        // Verify sensitive keys are stripped
        $this->assertArrayHasKey('email', $log->before_values);
        $this->assertArrayNotHasKey('password', $log->before_values);
        $this->assertArrayNotHasKey('remember_token', $log->before_values);
        $this->assertArrayNotHasKey('password', $log->after_values);
        $this->assertArrayNotHasKey('password_confirmation', $log->after_values);
    }

    public function test_audit_log_enforces_strict_append_only_immutability(): void
    {
        $this->actingAs($this->owner);

        $log = AuditTrailService::record([
            'module' => 'sales',
            'action' => 'sale.completed',
        ]);

        $this->expectException(LogicException::class);
        $log->update(['action' => 'tampered.action']);
    }

    public function test_audit_log_blocks_deletion(): void
    {
        $this->actingAs($this->owner);

        $log = AuditTrailService::record([
            'module' => 'sales',
            'action' => 'sale.completed',
        ]);

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_inventory_ledger_service_records_snapshots_and_enforces_immutability(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::create([
            'name'                 => '11kg LPG Cylinder',
            'sku'                  => 'LPG-11KG',
            'price'                => 950.00,
            'cost_price'           => 780.00,
            'stock_quantity'       => 20,
            'empty_quantity'       => 5,
            'reorder_level'        => 8,
            'standard_capacity_kg' => 11.00,
            'created_by'           => $this->owner->id,
        ]);

        $movement = InventoryLedgerService::recordMovement($product, [
            'reference_no'      => 'OR-000001',
            'movement_type'     => InventoryMovement::TYPE_SALE_OUT,
            'filled_qty_before' => 20,
            'filled_qty_change' => -2,
            'filled_qty_after'  => 18,
            'empty_qty_before'  => 5,
            'empty_qty_change'  => 2,
            'empty_qty_after'   => 7,
            'unit_price'        => 950.00,
            'source_type'       => 'Order',
            'source_id'         => 1,
            'reason'            => 'POS Customer Sale (Swap)',
        ]);

        $this->assertNotNull($movement->movement_uuid);
        $this->assertEquals('11kg LPG Cylinder', $movement->product_name_snapshot);
        $this->assertEquals(20, $movement->filled_qty_before);
        $this->assertEquals(-2, $movement->filled_qty_change);
        $this->assertEquals(18, $movement->filled_qty_after);
        $this->assertEquals(5, $movement->empty_qty_before);
        $this->assertEquals(2, $movement->empty_qty_change);
        $this->assertEquals(7, $movement->empty_qty_after);
        $this->assertEquals($this->cashier->id, $movement->user_id);
        $this->assertEquals('Customer Sale (Out)', $movement->movement_label);

        $this->expectException(LogicException::class);
        $movement->update(['filled_qty_after' => 99]);
    }

    public function test_domain_models_support_new_accountability_columns_and_relationships(): void
    {
        $product = Product::create([
            'name'           => 'LPG Hose',
            'sku'            => 'ACC-HOSE-01',
            'price'          => 250.00,
            'cost_price'     => 150.00,
            'stock_quantity' => 10,
            'empty_quantity' => 0,
            'reorder_level'  => 5,
            'created_by'     => $this->manager->id,
            'updated_by'     => $this->owner->id,
        ]);

        $this->assertEquals($this->manager->id, $product->creator->id);
        $this->assertEquals($this->owner->id, $product->updater->id);

        $stockIn = StockIn::create([
            'product_id'         => $product->id,
            'user_id'            => $this->manager->id,
            'approved_by'        => $this->owner->id,
            'reference_no'       => 'DR-2026-001',
            'supplier_name'      => 'Petron Bulk Supply',
            'quantity_received'  => 15,
            'empty_returned_qty' => 0,
            'unit_cost'          => 150.00,
            'filled_qty_before'  => 10,
            'filled_qty_after'   => 25,
            'empty_qty_before'   => 0,
            'empty_qty_after'    => 0,
            'remarks'            => 'Restock delivery',
        ]);

        $this->assertEquals('Maria Manager', $stockIn->user->name);
        $this->assertEquals('Pedro Owner', $stockIn->approver->name);

        $stockOut = StockOut::create([
            'product_id'             => $product->id,
            'user_id'                => $this->manager->id,
            'approved_by'            => $this->owner->id,
            'reference_no'           => 'ADJ-2026-001',
            'movement_category'      => 'damaged',
            'quantity_removed'       => 1,
            'empty_quantity_removed' => 0,
            'filled_qty_before'      => 25,
            'filled_qty_after'       => 24,
            'empty_qty_before'       => 0,
            'empty_qty_after'        => 0,
            'reason'                 => 'Damaged packaging during unloading',
        ]);

        $this->assertEquals('Maria Manager', $stockOut->user->name);

        $customer = Customer::create([
            'first_name'    => 'Rosa',
            'last_name'     => 'Santos',
            'customer_type' => 'Household',
        ]);

        $creditAccount = CreditAccount::create([
            'customer_id'            => $customer->id,
            'credit_limit'           => 5000.00,
            'agreed_monthly_payment' => 1500.00,
            'is_active'              => true,
            'status'                 => CreditAccount::STATUS_APPROVED,
            'requested_by'           => $this->manager->id,
            'approved_by'            => $this->owner->id,
            'approved_at'            => now(),
            'approval_remarks'       => 'Verified regular household customer',
        ]);

        $this->assertEquals('5000.00', $creditAccount->credit_limit);
        $this->assertEquals('Maria Manager', $creditAccount->requester->name);
        $this->assertEquals('Pedro Owner', $creditAccount->approver->name);

        $payment = Payment::create([
            'credit_account_id' => $creditAccount->id,
            'user_id'           => $this->cashier->id,
            'amount'            => 500.00,
            'payment_method'    => 'Cash',
            'reference_no'      => 'PAY-0001',
            'notes'             => 'Partial settlement',
            'balance_before'    => 1000.00,
            'balance_after'     => 500.00,
        ]);

        $ledger = CreditLedger::create([
            'credit_account_id' => $creditAccount->id,
            'transaction_type'  => 'Payment',
            'amount'            => 500.00,
            'payment_id'        => $payment->id,
            'user_id'           => $this->cashier->id,
            'balance_before'    => 1000.00,
            'balance_after'     => 500.00,
            'notes'             => 'Partial settlement',
        ]);

        $this->assertEquals('Juan Cashier', $payment->user->name);
        $this->assertEquals('Juan Cashier', $ledger->user->name);
    }
}
