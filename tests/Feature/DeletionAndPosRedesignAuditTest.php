<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeletionAndPosRedesignAuditTest extends TestCase
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
            'last_name' => 'User',
            'role_id' => 1,
        ]);

        $this->manager = User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'User',
            'role_id' => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Owner',
            'last_name' => 'User',
            'role_id' => 3,
        ]);
    }

    public function test_manager_can_soft_delete_product_with_history_and_historical_receipt_loads()
    {
        $product = Product::create([
            'name' => 'Discontinued LPG Tank',
            'price' => 750,
            'stock_quantity' => 10,
            'empty_quantity' => 2,
            'standard_capacity_kg' => 11,
        ]);

        $customer = Customer::create([
            'first_name' => 'Historical',
            'last_name' => 'Customer',
            'customer_type' => 'Retail',
        ]);

        // Create an order referencing this product
        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 750.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'is_swap' => true,
            'subtotal' => 750.00,
        ]);

        // Manager soft-deletes the product (no hard blocks!)
        $response = $this->actingAs($this->manager)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success', 'Product deleted successfully.');
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Verify historical order and receipt still load seamlessly without 500 error
        $resOrder = $this->actingAs($this->manager)->get(route('orders.show', $order));
        $resOrder->assertOk();
        $resOrder->assertSee('Discontinued LPG Tank');
        $resOrder->assertSee('Print Receipt');
        $resOrder->assertSee('window.print()');
        $resOrder->assertSee('Back to History');

        $resReceipt = $this->actingAs($this->cashier)->get(route('pos.show', $order));
        $resReceipt->assertOk();
        $resReceipt->assertSee('Discontinued LPG Tank');
    }

    public function test_manager_can_soft_delete_customer_with_history_and_receipt_loads()
    {
        $customer = Customer::create([
            'first_name' => 'Carlos',
            'last_name' => 'Yulo',
            'customer_type' => 'Retail',
        ]);

        $product = Product::create([
            'name' => 'Standard LPG 11kg',
            'price' => 800,
            'stock_quantity' => 20,
        ]);

        $order = Order::create([
            'customer_id' => $customer->id,
            'user_id' => $this->cashier->id,
            'total_amount' => 800.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        // Soft-delete the customer
        $response = $this->actingAs($this->manager)->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success', 'Customer record deleted successfully.');
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        // Historical order still loads and displays customer name
        $resOrder = $this->actingAs($this->manager)->get(route('orders.show', $order));
        $resOrder->assertOk();
        $resOrder->assertSee('Carlos Yulo');

        $resReceipt = $this->actingAs($this->cashier)->get(route('pos.show', $order));
        $resReceipt->assertOk();
        $resReceipt->assertSee('Carlos Yulo');
    }

    public function test_owner_can_soft_delete_staff_with_processed_orders()
    {
        $staff = User::factory()->create([
            'first_name' => 'Resigned',
            'last_name' => 'Cashier',
            'role_id' => 1,
        ]);

        $order = Order::create([
            'user_id' => $staff->id,
            'total_amount' => 500.00,
            'payment_method' => 'Cash',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('users.destroy', $staff));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', 'Staff account deleted successfully.');
        $this->assertSoftDeleted('users', ['id' => $staff->id]);

        // Historical order receipt shows the cashier name without crashing
        $resReceipt = $this->actingAs($this->owner)->get(route('orders.show', $order));
        $resReceipt->assertOk();
        $resReceipt->assertSee('Resigned Cashier');
    }

    public function test_cashier_cannot_delete_product()
    {
        $product = Product::create([
            'name' => 'Sample Product',
            'price' => 500,
            'stock_quantity' => 0,
            'empty_quantity' => 0,
        ]);

        $response = $this->actingAs($this->cashier)->delete(route('products.destroy', $product));
        $response->assertStatus(403);
    }

    public function test_owner_cannot_delete_self()
    {
        $response = $this->actingAs($this->owner)->delete(route('users.destroy', $this->owner));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('users', ['id' => $this->owner->id, 'deleted_at' => null]);
    }

    public function test_owner_cannot_delete_sole_remaining_owner()
    {
        $secondOwner = User::factory()->create([
            'first_name' => 'Owner',
            'last_name' => 'Two',
            'role_id' => 3,
        ]);

        // Delete second owner
        $this->actingAs($this->owner)->delete(route('users.destroy', $secondOwner));
        $this->assertSoftDeleted('users', ['id' => $secondOwner->id]);

        // Now only 1 active owner remains ($this->owner). Deleting the sole owner fails
        $response = $this->actingAs($this->owner)->delete(route('users.destroy', $this->owner));
        $response->assertSessionHasErrors();
    }

    public function test_index_views_render_delete_buttons_and_alpine_modals()
    {
        $product = Product::create([
            'name' => '11kg Cylinder',
            'price' => 700,
            'stock_quantity' => 5,
        ]);

        $customer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'customer_type' => 'Retail',
        ]);

        // Products index
        $resProduct = $this->actingAs($this->manager)->get(route('products.index'));
        $resProduct->assertOk();
        $resProduct->assertSee('Delete');
        $resProduct->assertDontSee('confirm(');
        $resProduct->assertSee('showDeleteModal');

        // Customers index
        $resCustomer = $this->actingAs($this->manager)->get(route('customers.index'));
        $resCustomer->assertOk();
        $resCustomer->assertSee('Delete');
        $resCustomer->assertDontSee('confirm(');
        $resCustomer->assertSee('showDeleteModal');

        // Users index
        $resUser = $this->actingAs($this->owner)->get(route('users.index'));
        $resUser->assertOk();
        $resUser->assertSee('Delete');
        $resUser->assertDontSee('confirm(');
        $resUser->assertSee('showDeleteModal');
    }

    public function test_pos_view_renders_redesigned_cart_and_discount_options()
    {
        $response = $this->actingAs($this->cashier)->get(route('pos.create'));
        $response->assertOk();

        // Verify Cart UI redesign elements
        $response->assertSee('Current Order');
        $response->assertSee('Cart &amp; Checkout Summary', false);
        $response->assertSee('Unit Price');
        $response->assertSee('Quantity');
        $response->assertSee('Subtotal');
        $response->assertSee('Remove Item');

        // Verify Task 3 Discount workflow elements
        $response->assertSee('Discount Type');
        $response->assertSee('<option value="none">None</option>', false);
        $response->assertSee('<option value="senior">Senior</option>', false);
        $response->assertSee('<option value="regular">Regular</option>', false);
        $response->assertSee('Discount Amount');
    }

    public function test_pos_checkout_accepts_manual_discounts()
    {
        $product = Product::create([
            'name' => '11kg Cooking Gas',
            'price' => 1000.00,
            'stock_quantity' => 10,
            'standard_capacity_kg' => 11,
        ]);

        $customer = Customer::create([
            'first_name' => 'Juan',
            'last_name' => 'Luna',
            'customer_type' => 'Retail',
        ]);

        // Manual discount checkout: 100 discount on 1000 = 900 total
        $resSenior = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'senior',
            'discount_amount' => 100.00,
            'discount_reference_name' => 'Juan Luna',
            'discount_reference_id' => 'SNR-12345',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'is_swap' => true,
                ],
            ],
        ]);

        $resSenior->assertSessionHasNoErrors();
        $order = Order::latest('id')->first();
        $this->assertEquals(100.00, $order->discount_amount);
        $this->assertEquals('senior', $order->discount_type);
        $this->assertEquals('Juan Luna', $order->discount_reference_name);
        $this->assertEquals('SNR-12345', $order->discount_reference_id);
        $this->assertEquals(900.00, $order->total_amount);
    }

    public function test_pos_checkout_fails_if_discount_exceeds_subtotal()
    {
        $product = Product::create([
            'name' => 'Small Item',
            'price' => 100.00,
            'stock_quantity' => 10,
        ]);

        $customer = Customer::create([
            'first_name' => 'Rich',
            'last_name' => 'Guy',
            'customer_type' => 'Retail',
        ]);

        $response = $this->actingAs($this->cashier)->post(route('pos.store'), [
            'customer_id' => $customer->id,
            'payment_method' => 'Cash',
            'discount_type' => 'promo',
            'discount_amount' => 150.00, // Exceeds the 100.00 subtotal!
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'is_swap' => false,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['discount_amount']);
    }

    public function test_application_branding_and_title()
    {
        $this->assertEquals('Liberty LPG Center', config('app.name'));

        $response = $this->actingAs($this->cashier)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('<title>Liberty LPG Center</title>', false);
        $response->assertSee('favicon.ico');
        $response->assertSee('logo.png');

        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertFileExists(public_path('logo.png'));
    }
}
