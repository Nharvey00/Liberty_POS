<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4AuditTrailUiTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $manager;
    private User $owner;

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
            'first_name' => 'Jose',
            'last_name'  => 'Owner',
            'role_id'    => 3,
        ]);
    }

    public function test_role_based_access_control_restricts_audit_trail_and_inventory_ledger_to_managers_and_owners(): void
    {
        $log = AuditLog::create([
            'actor_user_id'         => $this->manager->id,
            'actor_name_snapshot'   => $this->manager->name,
            'actor_role_snapshot'   => 'Manager',
            'module'                => 'products',
            'action'                => 'product.created',
            'entity_type'           => 'Product',
            'entity_id'             => 1,
            'transaction_reference' => 'PRD-0001',
            'status'                => 'completed',
        ]);

        // Cashier receives 403 Forbidden
        $this->actingAs($this->cashier)->get(route('audit-logs.index'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('audit-logs.show', $log))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('audit-logs.export'))->assertForbidden();
        $this->actingAs($this->cashier)->get(route('inventory-movements.index'))->assertForbidden();

        // Manager and Owner receive 200 OK
        $this->actingAs($this->manager)->get(route('audit-logs.index'))->assertOk();
        $this->actingAs($this->manager)->get(route('audit-logs.show', $log))->assertOk();
        $this->actingAs($this->owner)->get(route('inventory-movements.index'))->assertOk();
    }

    public function test_audit_trail_index_filters_by_module_actor_status_and_search_and_shows_diff_page(): void
    {
        $product = Product::create([
            'name'           => 'Solane 11kg',
            'sku'            => 'SOL-11',
            'price'          => 950.00,
            'stock_quantity' => 10,
            'empty_quantity' => 5,
        ]);

        // Perform a price change as Manager
        $this->actingAs($this->manager)->put(route('products.update', $product), [
            'name'  => 'Solane 11kg',
            'sku'   => 'SOL-11',
            'price' => 995.00,
        ])->assertRedirect();

        $priceLog = AuditLog::where('action', 'product.price_changed')->firstOrFail();

        // Filter by module=products and search=SOL-11
        $response = $this->actingAs($this->owner)->get(route('audit-logs.index', [
            'module'        => 'products',
            'actor_user_id' => $this->manager->id,
            'status'        => 'completed',
            'search'        => 'SOL-11',
        ]));

        $response->assertOk();
        $response->assertSee('Product Price Updated');
        $response->assertDontSee('product.price_changed');
        $response->assertSee('SOL-11');
        $response->assertSee('Maria Manager');

        // Category tab: "Products" shows the event, "Sales" does not
        $this->actingAs($this->owner)->get(route('audit-logs.index', ['category' => 'products']))
            ->assertOk()
            ->assertSee('Product Price Updated');
        $this->actingAs($this->owner)->get(route('audit-logs.index', ['category' => 'sales']))
            ->assertOk()
            ->assertDontSee('Product Price Updated')
            ->assertSee('No activity found');

        // Searching by the readable label finds the event
        $this->actingAs($this->owner)->get(route('audit-logs.index', ['search' => 'Price Updated']))
            ->assertOk()
            ->assertSee('SOL-11');

        // Detail view: readable labels and formatted values; no technical metadata exposed to non-IT users
        $detailResponse = $this->actingAs($this->owner)->get(route('audit-logs.show', $priceLog));
        $detailResponse->assertOk();
        $detailResponse->assertSee('Changes Recorded');
        $detailResponse->assertSee('Refill Price');
        $detailResponse->assertSee('₱950.00');
        $detailResponse->assertSee('₱995.00');
        $detailResponse->assertDontSee('Technical reference');
        $detailResponse->assertDontSee($priceLog->event_uuid);
    }

    public function test_audit_trail_csv_export_streams_csv_and_records_audit_exported_event(): void
    {
        AuditLog::create([
            'actor_user_id'         => $this->owner->id,
            'actor_name_snapshot'   => $this->owner->name,
            'actor_role_snapshot'   => 'Owner',
            'module'                => 'sales',
            'action'                => 'sale.completed',
            'entity_type'           => 'Order',
            'entity_id'             => 100,
            'transaction_reference' => 'INV-20261014-0100',
            'status'                => 'completed',
            'reason'                => 'POS Sale Completed',
        ]);

        $response = $this->actingAs($this->owner)->get(route('audit-logs.export', [
            'module' => 'sales',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csvContent = $response->streamedContent();
        $this->assertStringContainsString('INV-20261014-0100', $csvContent);
        $this->assertStringContainsString('sale.completed', $csvContent);

        // Exporting must itself be audited
        $exportLog = AuditLog::where('action', 'audit.exported')->first();
        $this->assertNotNull($exportLog);
        $this->assertSame($this->owner->id, $exportLog->actor_user_id);
    }

    public function test_contextual_audit_and_movement_histories_render_on_order_product_and_credit_account_pages(): void
    {
        // Create Product via controller to trigger initial_stock movement + product.created audit
        $this->actingAs($this->manager)->post(route('products.store'), [
            'name'           => 'Petron Gasul 11kg',
            'sku'            => 'PG-11',
            'price'          => 980.00,
            'stock_quantity' => 15,
            'empty_quantity' => 4,
        ])->assertRedirect();

        $product = Product::where('sku', 'PG-11')->firstOrFail();

        // Complete a POS sale as Cashier
        $this->actingAs($this->cashier)->post(route('pos.store'), [
            'payment_method' => 'Cash',
            'items'          => [
                [
                    'product_id' => $product->id,
                    'quantity'   => 2,
                    'is_swap'    => true,
                ],
            ],
        ])->assertRedirect();

        $order = Order::latest('id')->firstOrFail();

        // Create Customer + Credit Account as Manager
        $customer = Customer::create([
            'first_name'    => 'Elena',
            'last_name'     => 'Cruz',
            'customer_type' => 'Regular',
        ]);

        $this->actingAs($this->manager)->post(route('credit-accounts.store'), [
            'customer_id'      => $customer->id,
            'credit_limit'     => 10000.00,
            'status'           => 'approved',
            'approval_remarks' => 'Approved regular customer credit line',
        ])->assertRedirect();

        $creditAccount = CreditAccount::where('customer_id', $customer->id)->firstOrFail();

        // 1. Order Show Page displays the collapsible Activity History with readable labels
        $this->actingAs($this->manager)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Activity History')
            ->assertSee('Stock Movements')
            ->assertSee('Sale Completed')
            ->assertDontSee('sale.completed')
            ->assertSee('Juan Cashier');

        // 2. Product Show Page displays Unified Inventory Movement Ledger & Product Audit History
        $this->actingAs($this->manager)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Unified Inventory Movement Ledger')
            ->assertSee('Product Pricing & Catalog Audit History')
            ->assertSee('Product Registered');

        // 3. Credit Account Show Page displays Credit Account Terms & Approval Audit History
        $this->actingAs($this->manager)
            ->get(route('credit-accounts.show', $creditAccount))
            ->assertOk()
            ->assertSee('Credit Account Terms & Approval Audit History')
            ->assertSee('Credit Account Approved');

        // 4. Inventory Ledger renders movements filtered by product, and the "Sales" tab isolates sale deductions
        $this->actingAs($this->manager)
            ->get(route('inventory-movements.index', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('Petron Gasul 11kg')
            ->assertSee('Customer Sale')
            ->assertSee('Opening Stock');

        $this->actingAs($this->manager)
            ->get(route('inventory-movements.index', ['group' => 'sales']))
            ->assertOk()
            ->assertSee('Customer Sale')
            ->assertDontSee('Initial stock registered upon product creation');
    }
}
