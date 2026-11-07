<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * Phase 5: End-to-end acceptance criteria for the Audit Trail & User Accountability Module.
 *
 * Complements the Phase 1–4 suites by verifying cross-cutting guarantees:
 * forgery resistance, negative-stock prevention, ledger reconciliation,
 * tamper-proof audit records, role-based restrictions, and credential redaction.
 */
class AuditTrailAccountabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $otherCashier;
    protected User $manager;
    protected User $owner;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Cashier']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Manager']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Owner']);

        $this->cashier      = User::factory()->create(['first_name' => 'Ana',    'last_name' => 'Cashier', 'role_id' => 1]);
        $this->otherCashier = User::factory()->create(['first_name' => 'Ben',    'last_name' => 'Cashier', 'role_id' => 1]);
        $this->manager      = User::factory()->create(['first_name' => 'Celia',  'last_name' => 'Manager', 'role_id' => 2]);
        $this->owner        = User::factory()->create(['first_name' => 'Dario',  'last_name' => 'Owner',   'role_id' => 3]);

        $this->product = Product::create([
            'name'           => '11kg Regasco Cylinder',
            'sku'            => 'REG-11KG',
            'price'          => 1000.00,
            'cost_price'     => 800.00,
            'stock_quantity' => 10,
            'empty_quantity' => 2,
        ]);
    }

    private function completeCashSale(User $actor, int $quantity, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($actor)->post(route('pos.store'), array_merge([
            'payment_method' => 'Cash',
            'items'          => [[
                'product_id' => $this->product->id,
                'quantity'   => $quantity,
                'is_swap'    => true,
            ]],
        ], $extra));
    }

    public function test_forged_user_identifiers_in_request_payload_are_ignored(): void
    {
        // Cashier attempts to attribute the sale to another cashier and the owner
        $this->completeCashSale($this->cashier, 1, [
            'user_id'       => $this->otherCashier->id,
            'actor_user_id' => $this->owner->id,
            'cashier_id'    => $this->owner->id,
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame($this->cashier->id, $order->user_id);

        $audit = AuditLog::where('action', 'sale.completed')->firstOrFail();
        $this->assertSame($this->cashier->id, $audit->actor_user_id);
        $this->assertSame('Ana Cashier', $audit->actor_name_snapshot);
        $this->assertSame('Cashier', $audit->actor_role_snapshot);

        $movement = InventoryMovement::where('source_type', 'Order')->where('source_id', $order->id)->firstOrFail();
        $this->assertSame($this->cashier->id, $movement->user_id);
    }

    public function test_sales_and_stock_outs_cannot_drive_inventory_negative(): void
    {
        // Sale exceeding stock is rejected atomically
        $this->completeCashSale($this->cashier, 11)->assertSessionHasErrors();

        $this->assertSame(0, Order::count());
        $this->assertSame(0, InventoryMovement::count());
        $this->assertSame(10, $this->product->fresh()->stock_quantity);

        // Stock-out exceeding stock is rejected atomically
        $this->actingAs($this->manager)->post(route('stock-outs.store'), [
            'product_id'             => $this->product->id,
            'quantity_removed'       => 25,
            'empty_quantity_removed' => 0,
            'reason'                 => 'Damaged / leaking',
        ])->assertSessionHasErrors();

        $this->assertSame(0, InventoryMovement::count());
        $this->assertSame(10, $this->product->fresh()->stock_quantity);
    }

    public function test_inventory_ledger_reconciles_with_product_stock_after_mixed_operations(): void
    {
        // Stock-in +5 filled, -1 empty returned to supplier
        $this->actingAs($this->manager)->post(route('stock-ins.store'), [
            'product_id'         => $this->product->id,
            'reference_no'       => 'PO-RECON-01',
            'quantity_received'  => 5,
            'empty_returned_qty' => 1,
            'unit_cost'          => 805.00,
        ])->assertSessionHasNoErrors();

        // Sale -3 filled, +3 empty (swap)
        $this->completeCashSale($this->cashier, 3)->assertSessionHasNoErrors();
        $saleOrder = Order::latest('id')->firstOrFail();

        // Stock-out -2 filled (damaged)
        $this->actingAs($this->manager)->post(route('stock-outs.store'), [
            'product_id'             => $this->product->id,
            'reference_no'           => 'ADJ-RECON-01',
            'quantity_removed'       => 2,
            'empty_quantity_removed' => 0,
            'reason'                 => 'Damaged / leaking',
        ])->assertSessionHasNoErrors();

        // Void the sale: +3 filled, -3 empty
        $this->actingAs($this->manager)->post(route('orders.void.store', $saleOrder), [
            'void_reason' => 'Reconciliation test reversal',
        ])->assertSessionHasNoErrors();

        $product   = $this->product->fresh();
        $movements = InventoryMovement::where('product_id', $product->id)->orderBy('id')->get();

        $this->assertCount(4, $movements);

        // Each movement's "after" must equal the next movement's "before" (unbroken chain)
        for ($i = 1; $i < $movements->count(); $i++) {
            $this->assertSame($movements[$i - 1]->filled_qty_after, $movements[$i]->filled_qty_before);
            $this->assertSame($movements[$i - 1]->empty_qty_after, $movements[$i]->empty_qty_before);
        }

        // Opening balance + sum of changes == current stock
        $this->assertSame(10 + (int) $movements->sum('filled_qty_change'), (int) $product->stock_quantity);
        $this->assertSame(2 + (int) $movements->sum('empty_qty_change'), (int) $product->empty_quantity);
        $this->assertSame($movements->last()->filled_qty_after, (int) $product->stock_quantity);
        $this->assertSame(13, (int) $product->stock_quantity);
    }

    public function test_audit_records_are_tamper_proof_at_model_and_http_layers(): void
    {
        $this->completeCashSale($this->cashier, 1)->assertSessionHasNoErrors();

        $audit    = AuditLog::firstOrFail();
        $movement = InventoryMovement::firstOrFail();

        // Model layer: append-only
        try {
            $audit->update(['reason' => 'tampered']);
            $this->fail('AuditLog update should have thrown a LogicException.');
        } catch (LogicException) {
        }

        try {
            $movement->delete();
            $this->fail('InventoryMovement delete should have thrown a LogicException.');
        } catch (LogicException) {
        }

        $this->assertNotSame('tampered', $audit->fresh()->reason);
        $this->assertSame(1, InventoryMovement::count());

        // HTTP layer: no write endpoints exist, even for the Owner
        $this->actingAs($this->owner)->put('/audit-logs/' . $audit->id, ['reason' => 'tampered'])->assertStatus(405);
        $this->actingAs($this->owner)->delete('/audit-logs/' . $audit->id)->assertStatus(405);
        $this->actingAs($this->owner)->post('/audit-logs', ['action' => 'forged.event'])->assertStatus(405);
        $this->assertNotSame('tampered', $audit->fresh()->reason);
    }

    public function test_cashiers_cannot_void_sales_or_access_accountability_reports(): void
    {
        $this->completeCashSale($this->cashier, 1)->assertSessionHasNoErrors();
        $order = Order::firstOrFail();

        $this->actingAs($this->cashier)->post(route('orders.void.store', $order), [
            'void_reason' => 'Unauthorized void attempt',
        ])->assertForbidden();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(0, AuditLog::where('action', 'sale.voided')->count());

        $this->actingAs($this->cashier)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('audit-logs.export'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('inventory-movements.index'))->assertForbidden();
        $this->assertSame(0, AuditLog::where('action', 'audit.exported')->count());
    }

    public function test_user_creation_audit_never_stores_credentials(): void
    {
        $this->actingAs($this->owner)->post(route('users.store'), [
            'first_name'            => 'Elmer',
            'last_name'             => 'Staff',
            'email'                 => 'elmer@liberty.test',
            'password'              => 'SuperSecret123!',
            'password_confirmation' => 'SuperSecret123!',
            'role_id'               => 1,
        ])->assertSessionHasNoErrors();

        $audit = AuditLog::where('action', 'user.created')->firstOrFail();
        $this->assertSame($this->owner->id, $audit->actor_user_id);

        $serialized = json_encode([$audit->before_values, $audit->after_values]);
        $this->assertStringNotContainsString('SuperSecret123!', $serialized);
        $this->assertStringNotContainsString('$2y$', $serialized);
        $this->assertArrayNotHasKey('password', $audit->after_values ?? []);
        $this->assertArrayNotHasKey('remember_token', $audit->after_values ?? []);
    }

    public function test_credit_sale_and_payment_balances_reconcile_with_account_balance(): void
    {
        $customer = Customer::create([
            'first_name'    => 'Fe',
            'last_name'     => 'Santos',
            'customer_type' => 'Household',
        ]);

        $account = CreditAccount::create([
            'customer_id'  => $customer->id,
            'credit_limit' => 5000.00,
            'is_active'    => true,
            'status'       => CreditAccount::STATUS_APPROVED,
        ]);

        $this->completeCashSale($this->cashier, 2, [
            'customer_id'    => $customer->id,
            'payment_method' => 'Credit',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->otherCashier)->post(route('payments.store', $account), [
            'amount'         => 750.00,
            'payment_method' => 'Cash',
            'reference_no'   => 'OR-RECON-01',
        ])->assertSessionHasNoErrors();

        $ledgers = CreditLedger::where('credit_account_id', $account->id)->orderBy('id')->get();
        $this->assertCount(2, $ledgers);

        // Unbroken balance chain and distinct accountable actors
        $this->assertEquals(0.00, (float) $ledgers[0]->balance_before);
        $this->assertEquals((float) $ledgers[0]->balance_after, (float) $ledgers[1]->balance_before);
        $this->assertSame($this->cashier->id, $ledgers[0]->user_id);
        $this->assertSame($this->otherCashier->id, $ledgers[1]->user_id);

        $this->assertEquals(1250.00, (float) $ledgers[1]->balance_after);
        $this->assertEquals((float) $ledgers[1]->balance_after, (float) $account->fresh()->remaining_balance);
    }
}
