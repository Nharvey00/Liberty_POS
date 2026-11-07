<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\TankLoan;
use App\Models\TankLoanReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhasesOneToFourAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
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
            'last_name' => 'Staff',
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'Staff',
            'role_id' => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Owner',
            'last_name' => 'Admin',
            'role_id' => 3,
        ]);
    }

    public function test_phase_1_customer_discount_types_and_pos_fixed_vs_percentage_calculations()
    {
        // 1. Create Industrial customer with fixed Peso discount > 100 (e.g., ₱150.00)
        $res = $this->actingAs($this->manager)->post(route('customers.store'), [
            'first_name' => 'Metro',
            'last_name' => 'Foundry',
            'business_name' => 'Metro Foundry Corp',
            'customer_type' => 'Industrial',
            'default_discount_type' => 'industrial',
            'default_discount_percentage' => 150.00,
        ]);
        $res->assertRedirect(route('customers.index'));

        $industrialCustomer = Customer::where('business_name', 'Metro Foundry Corp')->firstOrFail();
        $this->assertEquals('industrial', $industrialCustomer->default_discount_type);
        $this->assertEquals(150.00, (float) $industrialCustomer->default_discount_percentage);

        // 2. Senior Citizen discount > 100% must fail validation
        $badSenior = $this->actingAs($this->manager)->post(route('customers.store'), [
            'first_name' => 'Lolo',
            'last_name' => 'Jose',
            'customer_type' => 'Retail',
            'default_discount_type' => 'senior',
            'default_discount_percentage' => 120.00,
        ]);
        $badSenior->assertSessionHasErrors('default_discount_percentage');

        // 3. POS Checkout with Fixed Peso discount (Tertiary / Regular / Industrial)
        $product = Product::create([
            'name' => '11kg LPG Cylinder',
            'price' => 900.00,
            'stock_quantity' => 20,
            'empty_quantity' => 5,
            'standard_capacity_kg' => 11,
        ]);

        $posFixed = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id' => $industrialCustomer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'industrial',
            'discount_amount' => 150.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);
        $posFixed->assertRedirect();

        $orderFixed = Order::latest('id')->first();
        $this->assertEquals(150.00, (float) $orderFixed->discount_amount);
        $this->assertEquals(750.00, (float) $orderFixed->total_amount);

        // 4. POS Checkout with Senior Percentage discount (20% of 900 = 180, net = 720)
        $posSenior = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id' => $industrialCustomer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'senior',
            'discount_rate' => 20,
            'discount_amount' => 180.00,
            'discount_reference_name' => 'Lolo Jose',
            'discount_reference_id' => 'OSCA-9988',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);
        $posSenior->assertSessionHasNoErrors();
        $posSenior->assertRedirect();

        $orderSenior = Order::latest('id')->first();
        $this->assertEquals('senior', $orderSenior->discount_type);
        $this->assertEquals(180.00, (float) $orderSenior->discount_amount);
        $this->assertEquals(720.00, (float) $orderSenior->total_amount);
    }

    public function test_phase_2_voidable_stock_in_and_stock_out_restricted_to_admin_owner_with_safeguards()
    {
        $product = Product::create([
            'name' => '50kg Commercial Tank',
            'price' => 3500.00,
            'stock_quantity' => 5,
            'empty_quantity' => 10,
            'standard_capacity_kg' => 50,
        ]);

        // Record a Stock In (+10 filled, -4 empty returned to supplier)
        $this->actingAs($this->manager)->post(route('stock-ins.store'), [
            'product_id' => $product->id,
            'quantity_received' => 10,
            'empty_returned_qty' => 4,
            'remarks' => 'Supplier batch delivery',
        ])->assertRedirect(route('stock-ins.index'));

        $product->refresh();
        $this->assertEquals(15, $product->stock_quantity);
        $this->assertEquals(6, $product->empty_quantity);

        $stockIn = StockIn::latest('id')->firstOrFail();

        // Cashier (Level 1) and Manager (Level 2) CANNOT void Stock In
        $this->actingAs($this->cashier)->post(route('stock-ins.void', $stockIn), [
            'void_reason' => 'Unauthorized attempt',
        ])->assertStatus(403);

        $this->actingAs($this->manager)->post(route('stock-ins.void', $stockIn), [
            'void_reason' => 'Manager attempt',
        ])->assertStatus(403);

        // HARD SAFEGUARD: If current stock drops below quantity_received (e.g., 8 tanks sold leaving 7 < 10), block voiding
        $product->update(['stock_quantity' => 7]);

        $blockedVoid = $this->actingAs($this->owner)->from(route('stock-ins.index'))->post(route('stock-ins.void', $stockIn), [
            'void_reason' => 'Try voiding after tanks were sold',
        ]);
        $blockedVoid->assertRedirect(route('stock-ins.index'));
        $blockedVoid->assertSessionHasErrors('void_reason');
        $this->assertFalse($stockIn->fresh()->is_voided);

        // Restore stock to 15 so voiding is safe
        $product->update(['stock_quantity' => 15]);

        $validVoid = $this->actingAs($this->owner)->post(route('stock-ins.void', $stockIn), [
            'void_reason' => 'Duplicate PO entry logged by mistake',
        ]);
        $validVoid->assertRedirect(route('stock-ins.index'));

        $stockIn->refresh();
        $product->refresh();
        $this->assertTrue($stockIn->is_voided);
        $this->assertEquals($this->owner->id, $stockIn->voided_by);
        $this->assertEquals(5, $product->stock_quantity);
        $this->assertEquals(10, $product->empty_quantity);

        // Record a Stock Out (-2 filled, -1 empty) and void it as Admin Owner
        $this->actingAs($this->manager)->post(route('stock-outs.store'), [
            'product_id' => $product->id,
            'quantity_removed' => 2,
            'empty_quantity_removed' => 1,
            'reason' => 'Damaged / leaking',
        ])->assertRedirect(route('stock-outs.index'));

        $product->refresh();
        $this->assertEquals(3, $product->stock_quantity);
        $this->assertEquals(9, $product->empty_quantity);

        $stockOut = StockOut::latest('id')->firstOrFail();

        // Manager cannot void Stock Out
        $this->actingAs($this->manager)->post(route('stock-outs.void', $stockOut), [
            'void_reason' => 'Manager attempt',
        ])->assertStatus(403);

        // Owner voids Stock Out -> restores 2 filled and 1 empty
        $this->actingAs($this->owner)->post(route('stock-outs.void', $stockOut), [
            'void_reason' => 'Valve repaired, tank not damaged',
        ])->assertRedirect(route('stock-outs.index'));

        $product->refresh();
        $stockOut->refresh();
        $this->assertTrue($stockOut->is_voided);
        $this->assertEquals(5, $product->stock_quantity);
        $this->assertEquals(10, $product->empty_quantity);
    }

    public function test_phase_3_customer_monthly_purchase_and_discount_audit_excludes_voided_sales()
    {
        $customer = Customer::create([
            'first_name' => 'Elena',
            'last_name' => 'Bakeshop',
            'customer_type' => 'Tertiary',
            'default_discount_type' => 'tertiary',
            'default_discount_percentage' => 50.00,
        ]);

        $product = Product::create([
            'name' => '11kg LPG Refill',
            'price' => 1000.00,
            'stock_quantity' => 50,
            'empty_quantity' => 10,
            'standard_capacity_kg' => 11,
        ]);

        // Valid Order: 2 tanks (Gross 2000, Discount 50, Net 1950)
        $validOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 1950.00,
            'discount_type' => 'tertiary',
            'discount_amount' => 50.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);
        OrderItem::create([
            'order_id' => $validOrder->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'is_swap' => true,
            'subtotal' => 2000.00,
        ]);

        // Voided Order: 5 tanks (Gross 5000, Discount 200, Net 4800) - MUST BE EXCLUDED FROM MONTHLY SUMMARY
        $voidedOrder = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 4800.00,
            'discount_type' => 'tertiary',
            'discount_amount' => 200.00,
            'payment_method' => 'Cash',
            'status' => 'voided',
            'is_voided' => true,
        ]);
        OrderItem::create([
            'order_id' => $voidedOrder->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'is_swap' => true,
            'subtotal' => 5000.00,
        ]);

        $res = $this->actingAs($this->manager)->get(route('customers.show', $customer));
        $res->assertOk();
        $res->assertViewHas('lifetimeSummary', function ($summary) {
            return $summary['total_qty'] === 2
                && $summary['gross_amount'] === 2000.0
                && $summary['total_discount'] === 50.0
                && $summary['net_spend'] === 1950.0;
        });
        $res->assertSee('Monthly Purchase & Discount Summary Breakdown');
        $res->assertSee('Itemized Purchase Audit Log');
        $res->assertSee('11kg LPG Refill');
    }

    public function test_phase_4_loaned_tanks_huram_dual_entry_return_and_void_synchronization()
    {
        $customer = Customer::create([
            'first_name' => 'Ramon',
            'last_name' => 'Eatery',
            'customer_type' => 'Regular',
        ]);

        $product = Product::create([
            'name' => '22kg LPG Tank',
            'price' => 1800.00,
            'stock_quantity' => 10,
            'empty_quantity' => 4,
            'standard_capacity_kg' => 22,
        ]);

        // Entry Point A: Stock Out with reason "Loaned Tank (Huram)" (Borrow 3 Filled Tanks)
        $this->actingAs($this->manager)->post(route('stock-outs.store'), [
            'product_id' => $product->id,
            'reason' => 'Loaned Tank (Huram)',
            'customer_id' => $customer->id,
            'tank_condition_out' => 'filled',
            'loan_quantity' => 3,
            'remarks' => 'Borrowed for fiesta catering',
        ])->assertRedirect(route('stock-outs.index'));

        $product->refresh();
        $this->assertEquals(7, $product->stock_quantity);
        $this->assertEquals(3, $customer->fresh()->borrowed_tanks_count);

        $loanA = TankLoan::where('customer_id', $customer->id)->firstOrFail();
        $this->assertEquals(3, $loanA->quantity_borrowed);
        $this->assertEquals('borrowed', $loanA->status);

        // Verify GET /tank-loans, GET /tank-loans/create, and GET /tank-loans/customer/{customer} render with 200 OK
        $this->actingAs($this->manager)->get(route('tank-loans.index'))
            ->assertOk()
            ->assertSee('Loaned Tanks (Huram)')
            ->assertSee('Ramon Eatery');

        $this->actingAs($this->manager)->get(route('tank-loans.create'))
            ->assertOk()
            ->assertSee('Record New Loaned Tank (Huram)');

        $this->actingAs($this->manager)->get(route('tank-loans.customer', $customer))
            ->assertOk()
            ->assertSee('Borrowed: 3 Tank(s)');

        // Customer index & show pages display "Borrowed: 3 Tank(s)"
        $this->actingAs($this->manager)->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Borrowed: 3 Tank(s)');

        // Partial Return: Customer returns 2 tanks as Empty Shells
        $this->actingAs($this->manager)->post(route('tank-loans.return', $loanA), [
            'quantity_returned' => 2,
            'returned_as' => 'empty_shell',
            'notes' => 'Returned 2 empty shells',
        ])->assertRedirect();

        $loanA->refresh();
        $product->refresh();
        $this->assertEquals('partially_returned', $loanA->status);
        $this->assertEquals(2, $loanA->quantity_returned);
        $this->assertEquals(6, $product->empty_quantity); // 4 + 2 empty shells returned
        $this->assertEquals(1, $customer->fresh()->borrowed_tanks_count);
        $this->assertEquals(2, (int) TankLoanReturn::valid()->sum('quantity_returned'));

        // Verify GET /tank-loans reflects 1 active borrowed tank and 2 returned today
        $this->actingAs($this->manager)->get(route('tank-loans.index'))
            ->assertOk()
            ->assertViewHas('totalActiveBorrowedTanks', 1)
            ->assertViewHas('totalReturnedToday', 2);

        // Safeguard: Trying to void the original StockOut while quantity_returned > 0 must be blocked
        $stockOutA = StockOut::findOrFail($loanA->stock_out_id);
        $blockedStockOutVoid = $this->actingAs($this->owner)->from(route('stock-outs.index'))->post(route('stock-outs.void', $stockOutA), [
            'void_reason' => 'Try voiding loan with active returns',
        ]);
        $blockedStockOutVoid->assertSessionHasErrors('void_reason');

        // Void the Return StockIn -> automatically reverses the return on TankLoan back to 3 unreturned!
        $returnRecord = TankLoanReturn::where('tank_loan_id', $loanA->id)->firstOrFail();
        $returnStockIn = StockIn::findOrFail($returnRecord->stock_in_id);

        $this->actingAs($this->owner)->post(route('stock-ins.void', $returnStockIn), [
            'void_reason' => 'Logged return under wrong customer',
        ])->assertRedirect(route('stock-ins.index'));

        $loanA->refresh();
        $product->refresh();
        $this->assertEquals(0, $loanA->quantity_returned);
        $this->assertEquals('borrowed', $loanA->status);
        $this->assertEquals(4, $product->empty_quantity); // back to 4
        $this->assertEquals(3, $customer->fresh()->borrowed_tanks_count);
        $this->assertEquals(0, (int) TankLoanReturn::valid()->sum('quantity_returned'));

        // Now that quantity_returned == 0, Admin Owner can void the original StockOut -> marks TankLoan as voided!
        $this->actingAs($this->owner)->post(route('stock-outs.void', $stockOutA), [
            'void_reason' => 'Cancelled fiesta loan',
        ])->assertRedirect(route('stock-outs.index'));

        $loanA->refresh();
        $product->refresh();
        $this->assertEquals('voided', $loanA->status);
        $this->assertEquals(10, $product->stock_quantity);
        $this->assertEquals(0, $customer->fresh()->borrowed_tanks_count);
    }
}
