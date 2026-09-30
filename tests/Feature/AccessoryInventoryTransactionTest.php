<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccessoryInventoryTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $cashier;
    protected Product $cylinder;
    protected Product $accessory;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Cashier']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Manager']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Owner']);

        $this->manager = User::factory()->create(['role_id' => 2]);
        $this->cashier = User::factory()->create(['role_id' => 1]);

        $this->cylinder = Product::create([
            'name' => '11kg Test Cylinder',
            'price' => 950.00,
            'new_cylinder_price' => 1500.00,
            'stock_quantity' => 20,
            'empty_quantity' => 10,
            'standard_capacity_kg' => 11.00,
        ]);

        $this->accessory = Product::create([
            'name' => 'LPG Regulator Test',
            'price' => 450.00,
            'new_cylinder_price' => null,
            'stock_quantity' => 30,
            'empty_quantity' => 0,
            'standard_capacity_kg' => null,
        ]);
    }

    public function test_product_model_correctly_identifies_accessory_and_cylinder(): void
    {
        $this->assertTrue($this->accessory->isAccessory());
        $this->assertFalse($this->accessory->isCylinder());
        $this->assertTrue($this->accessory->is_accessory);
        $this->assertFalse($this->accessory->is_cylinder);

        $this->assertFalse($this->cylinder->isAccessory());
        $this->assertTrue($this->cylinder->isCylinder());
        $this->assertFalse($this->cylinder->is_accessory);
        $this->assertTrue($this->cylinder->is_cylinder);

        $array = $this->accessory->toArray();
        $this->assertArrayHasKey('is_accessory', $array);
        $this->assertArrayHasKey('is_cylinder', $array);
        $this->assertTrue($array['is_accessory']);
        $this->assertFalse($array['is_cylinder']);
    }

    public function test_stock_in_forces_empty_returned_quantity_to_zero_for_accessories(): void
    {
        // Even if empty_returned_qty is submitted with a positive integer, it should be forced to 0
        $response = $this->actingAs($this->manager)->post(route('stock-ins.store'), [
            'product_id' => $this->accessory->id,
            'quantity_received' => 15,
            'empty_returned_qty' => 5,
            'remarks' => 'Restock regulators',
        ]);

        $response->assertRedirect(route('stock-ins.index'));

        $this->accessory->refresh();
        $this->assertEquals(45, $this->accessory->stock_quantity); // 30 + 15
        $this->assertEquals(0, $this->accessory->empty_quantity);  // Unchanged at 0

        $stockIn = StockIn::where('product_id', $this->accessory->id)->latest()->first();
        $this->assertNotNull($stockIn);
        $this->assertEquals(15, $stockIn->quantity_received);
        $this->assertEquals(0, $stockIn->empty_returned_qty);
    }

    public function test_stock_out_forces_empty_quantity_removed_to_zero_for_accessories(): void
    {
        // Even if empty_quantity_removed is submitted with a positive integer, it should be forced to 0
        $response = $this->actingAs($this->manager)->post(route('stock-outs.store'), [
            'product_id' => $this->accessory->id,
            'quantity_removed' => 5,
            'empty_quantity_removed' => 2,
            'reason' => 'Damaged / leaking',
            'remarks' => 'Defective valve',
        ]);

        $response->assertRedirect(route('stock-outs.index'));

        $this->accessory->refresh();
        $this->assertEquals(25, $this->accessory->stock_quantity); // 30 - 5
        $this->assertEquals(0, $this->accessory->empty_quantity);  // Unchanged at 0

        $stockOut = StockOut::where('product_id', $this->accessory->id)->latest()->first();
        $this->assertNotNull($stockOut);
        $this->assertEquals(5, $stockOut->quantity_removed);
        $this->assertEquals(0, $stockOut->empty_quantity_removed);
    }

    public function test_pos_checkout_with_accessory_does_not_increment_empty_shells(): void
    {
        $response = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'payment_method' => 'Cash',
            'items' => [
                [
                    'product_id' => $this->accessory->id,
                    'quantity' => 3,
                    'is_swap' => false,
                ]
            ],
        ]);

        $response->assertRedirect();

        $this->accessory->refresh();
        $this->assertEquals(27, $this->accessory->stock_quantity); // 30 - 3
        $this->assertEquals(0, $this->accessory->empty_quantity);  // Accessory never increments empties
    }

    public function test_order_voiding_restores_accessory_stock_without_touching_empty_shells(): void
    {
        // Create an order with an accessory
        $order = Order::create([
            'payment_method' => 'Cash',
            'total_amount' => 900.00,
            'user_id' => $this->cashier->id,
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->accessory->id,
            'quantity' => 2,
            'is_swap' => false,
            'subtotal' => 900.00,
        ]);

        // Manually adjust accessory stock to simulate post-checkout state
        $this->accessory->decrement('stock_quantity', 2);
        $this->assertEquals(28, $this->accessory->fresh()->stock_quantity);
        $this->assertEquals(0, $this->accessory->fresh()->empty_quantity);

        // Manager voids the order
        $response = $this->actingAs($this->manager)->post(route('orders.void.store', $order), [
            'void_reason' => 'Customer changed mind and returned goods',
        ]);

        $response->assertRedirect(route('orders.show', $order));

        $this->accessory->refresh();
        $this->assertEquals(30, $this->accessory->stock_quantity); // Restored from 28 to 30
        $this->assertEquals(0, $this->accessory->empty_quantity);  // Remained 0 (never decremented into negatives)
    }
}
