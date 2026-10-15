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
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2SalesAndInventoryAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $manager;
    protected User $cashier;
    protected Product $cylinder;
    protected Customer $customer;
    protected CreditAccount $creditAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Cashier']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Manager']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Owner']);

        $this->cashier = User::factory()->create([
            'first_name' => 'Carlos',
            'last_name'  => 'Cashier',
            'role_id'    => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Elena',
            'last_name'  => 'Manager',
            'role_id'    => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Roberto',
            'last_name'  => 'Owner',
            'role_id'    => 3,
        ]);

        $this->cylinder = Product::create([
            'name'                 => '11kg Solane Cylinder',
            'sku'                  => 'SOL-11KG',
            'price'                => 1000.00,
            'cost_price'           => 820.00,
            'new_cylinder_price'   => 2200.00,
            'stock_quantity'       => 15,
            'empty_quantity'       => 4,
            'standard_capacity_kg' => 11.00,
        ]);

        $this->customer = Customer::create([
            'first_name'    => 'Lorna',
            'last_name'     => 'Tolentino',
            'customer_type' => 'Household',
        ]);

        $this->creditAccount = CreditAccount::create([
            'customer_id'            => $this->customer->id,
            'credit_limit'           => 3000.00,
            'agreed_monthly_payment' => 1000.00,
            'is_active'              => true,
            'status'                 => CreditAccount::STATUS_APPROVED,
        ]);
    }

    public function test_pos_sale_records_unit_price_inventory_movement_and_audit_log(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id'    => $this->customer->id,
            'payment_method' => 'Credit',
            'items'          => [
                [
                    'product_id' => $this->cylinder->id,
                    'quantity'   => 2,
                    'is_swap'    => true,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertEquals($this->cashier->id, $order->user_id);

        // 1. Verify OrderItem unit_price snapshot
        $item = $order->items()->firstOrFail();
        $this->assertEquals('1000.00', $item->unit_price);
        $this->assertEquals('2000.00', $item->subtotal);

        // 2. Verify InventoryMovement ledger entry
        $movement = InventoryMovement::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->firstOrFail();

        $this->assertEquals(InventoryMovement::TYPE_SALE_OUT, $movement->movement_type);
        $this->assertEquals(15, $movement->filled_qty_before);
        $this->assertEquals(-2, $movement->filled_qty_change);
        $this->assertEquals(13, $movement->filled_qty_after);
        $this->assertEquals(4, $movement->empty_qty_before);
        $this->assertEquals(2, $movement->empty_qty_change);
        $this->assertEquals(6, $movement->empty_qty_after);
        $this->assertEquals($this->cashier->id, $movement->user_id);

        // 3. Verify CreditLedger balance snapshots and actor
        $ledger = CreditLedger::where('order_id', $order->id)->firstOrFail();
        $this->assertEquals($this->cashier->id, $ledger->user_id);
        $this->assertEquals('0.00', $ledger->balance_before);
        $this->assertEquals('2000.00', $ledger->balance_after);

        // 4. Verify AuditLog entry
        $audit = AuditLog::where('module', 'sales')
            ->where('action', 'sale.completed')
            ->where('entity_id', (string) $order->id)
            ->firstOrFail();

        $this->assertEquals($this->cashier->id, $audit->actor_user_id);
        $this->assertEquals('Carlos Cashier', $audit->actor_name_snapshot);
        $this->assertEquals('Cashier', $audit->actor_role_snapshot);
        $this->assertEquals($order->invoice_number, $audit->transaction_reference);
    }

    public function test_pos_enforces_credit_limit_when_configured(): void
    {
        // Credit limit is 3000; attempting to charge 4000 (4 x 1000) should fail
        $response = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id'    => $this->customer->id,
            'payment_method' => 'Credit',
            'items'          => [
                [
                    'product_id' => $this->cylinder->id,
                    'quantity'   => 4,
                    'is_swap'    => true,
                ],
            ],
        ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(0, Order::count());
        $this->assertEquals(15, $this->cylinder->fresh()->stock_quantity);
    }

    public function test_order_void_preserves_original_cashier_and_logs_reversal_movements(): void
    {
        // Cashier completes sale
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id'    => $this->customer->id,
            'payment_method' => 'Credit',
            'items'          => [
                [
                    'product_id' => $this->cylinder->id,
                    'quantity'   => 2,
                    'is_swap'    => true,
                ],
            ],
        ]);

        $order = Order::firstOrFail();

        // Manager voids the sale
        $voidResponse = $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Customer requested cancellation before dispatch',
        ]);

        $voidResponse->assertRedirect(route('orders.show', $order));
        $order->refresh();

        // Original cashier is strictly preserved, voided_by is Manager
        $this->assertEquals($this->cashier->id, $order->user_id);
        $this->assertEquals($this->manager->id, $order->voided_by);
        $this->assertEquals('voided', $order->status);

        // Verify void_restock InventoryMovement
        $restock = InventoryMovement::where('source_type', 'Order')
            ->where('source_id', $order->id)
            ->where('movement_type', InventoryMovement::TYPE_VOID_RESTOCK)
            ->firstOrFail();

        $this->assertEquals(13, $restock->filled_qty_before);
        $this->assertEquals(2, $restock->filled_qty_change);
        $this->assertEquals(15, $restock->filled_qty_after);
        $this->assertEquals(6, $restock->empty_qty_before);
        $this->assertEquals(-2, $restock->empty_qty_change);
        $this->assertEquals(4, $restock->empty_qty_after);
        $this->assertEquals($this->manager->id, $restock->user_id);
        $this->assertEquals($this->manager->id, $restock->approved_by);

        // Verify CreditLedger reversal snapshots
        $reversalLedger = CreditLedger::where('order_id', $order->id)
            ->where('transaction_type', 'Payment')
            ->firstOrFail();

        $this->assertEquals($this->manager->id, $reversalLedger->user_id);
        $this->assertEquals('2000.00', $reversalLedger->balance_before);
        $this->assertEquals('0.00', $reversalLedger->balance_after);

        // Verify sale.voided AuditLog
        $audit = AuditLog::where('module', 'sales')
            ->where('action', 'sale.voided')
            ->where('entity_id', (string) $order->id)
            ->firstOrFail();

        $this->assertEquals($this->manager->id, $audit->actor_user_id);
        $this->assertEquals($this->manager->id, $audit->approver_user_id);
        $this->assertEquals($this->cashier->id, $audit->after_values['original_cashier_id']);
        $this->assertEquals('Customer requested cancellation before dispatch', $audit->reason);
    }

    public function test_stock_in_and_stock_out_record_actor_snapshots_and_audit_logs(): void
    {
        // 1. Stock In
        $stockInRes = $this->actingAs($this->manager)->post(route('stock-ins.store'), [
            'product_id'         => $this->cylinder->id,
            'reference_no'       => 'PO-2026-888',
            'supplier_name'      => 'Solane Bulk Depot',
            'quantity_received'  => 10,
            'empty_returned_qty' => 3,
            'unit_cost'          => 835.00,
            'remarks'            => 'Weekly replenishment',
        ]);

        $stockInRes->assertRedirect(route('stock-ins.index'));

        $stockIn = StockIn::latest('id')->firstOrFail();
        $this->assertEquals($this->manager->id, $stockIn->user_id);
        $this->assertEquals(15, $stockIn->filled_qty_before);
        $this->assertEquals(25, $stockIn->filled_qty_after);
        $this->assertEquals(4, $stockIn->empty_qty_before);
        $this->assertEquals(1, $stockIn->empty_qty_after);

        $this->assertDatabaseHas('inventory_movements', [
            'source_type'       => 'StockIn',
            'source_id'         => $stockIn->id,
            'movement_type'     => InventoryMovement::TYPE_STOCK_IN,
            'filled_qty_before' => 15,
            'filled_qty_change' => 10,
            'filled_qty_after'  => 25,
            'user_id'           => $this->manager->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module'                => 'inventory',
            'action'                => 'stock.in',
            'transaction_reference' => 'PO-2026-888',
            'actor_user_id'         => $this->manager->id,
        ]);

        // 2. Stock Out (Damaged)
        $stockOutRes = $this->actingAs($this->manager)->post(route('stock-outs.store'), [
            'product_id'             => $this->cylinder->id,
            'reference_no'           => 'ADJ-2026-101',
            'quantity_removed'       => 2,
            'empty_quantity_removed' => 0,
            'reason'                 => 'Damaged / leaking',
            'remarks'                => 'Valve leak detected during inspection',
        ]);

        $stockOutRes->assertRedirect(route('stock-outs.index'));

        $stockOut = StockOut::latest('id')->firstOrFail();
        $this->assertEquals($this->manager->id, $stockOut->user_id);
        $this->assertEquals('damaged', $stockOut->movement_category);
        $this->assertEquals(25, $stockOut->filled_qty_before);
        $this->assertEquals(23, $stockOut->filled_qty_after);

        $this->assertDatabaseHas('inventory_movements', [
            'source_type'       => 'StockOut',
            'source_id'         => $stockOut->id,
            'movement_type'     => InventoryMovement::TYPE_DAMAGED_WRITEOFF,
            'filled_qty_before' => 25,
            'filled_qty_change' => -2,
            'filled_qty_after'  => 23,
            'user_id'           => $this->manager->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module'                => 'inventory',
            'action'                => 'stock.out',
            'transaction_reference' => 'ADJ-2026-101',
            'actor_user_id'         => $this->manager->id,
        ]);
    }

    public function test_product_lifecycle_and_price_changes_are_audited_with_before_and_after_values(): void
    {
        // 1. Create product with initial stock
        $createRes = $this->actingAs($this->manager)->post(route('products.store'), [
            'name'                 => '22kg Petronas Cylinder',
            'sku'                  => 'PET-22KG',
            'price'                => 1850.00,
            'cost_price'           => 1500.00,
            'new_cylinder_price'   => 3800.00,
            'standard_capacity_kg' => 22.00,
            'stock_quantity'       => 8,
            'empty_quantity'       => 2,
        ]);

        $createRes->assertRedirect(route('products.index'));
        $product = Product::where('sku', 'PET-22KG')->firstOrFail();

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'        => $product->id,
            'movement_type'     => InventoryMovement::TYPE_INITIAL_STOCK,
            'filled_qty_change' => 8,
            'filled_qty_after'  => 8,
            'user_id'           => $this->manager->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module'        => 'products',
            'action'        => 'product.created',
            'entity_id'     => (string) $product->id,
            'actor_user_id' => $this->manager->id,
        ]);

        // 2. Update price -> logs product.price_changed with old and new price
        $updateRes = $this->actingAs($this->owner)->put(route('products.update', $product), [
            'name'                 => '22kg Petronas Cylinder',
            'sku'                  => 'PET-22KG',
            'price'                => 1925.00,
            'new_cylinder_price'   => 3900.00,
            'standard_capacity_kg' => 22.00,
        ]);

        $updateRes->assertRedirect(route('products.index'));

        $priceAudit = AuditLog::where('module', 'products')
            ->where('action', 'product.price_changed')
            ->where('entity_id', (string) $product->id)
            ->firstOrFail();

        $this->assertEquals($this->owner->id, $priceAudit->actor_user_id);
        $this->assertEquals(1850.00, $priceAudit->before_values['price']);
        $this->assertEquals(1925.00, $priceAudit->after_values['price']);
    }
}
