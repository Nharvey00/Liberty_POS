<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAndNameRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Level 1']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Level 2']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Level 3']);
    }

    public function test_user_role_helper_methods(): void
    {
        $cashier = User::factory()->create(['role_id' => 1]);
        $manager = User::factory()->create(['role_id' => 2]);
        $owner = User::factory()->create(['role_id' => 3]);

        $this->assertTrue($cashier->isCashier());
        $this->assertFalse($cashier->isManagerOrOwner());
        $this->assertFalse($cashier->isOwner());

        $this->assertFalse($manager->isCashier());
        $this->assertTrue($manager->isManagerOrOwner());
        $this->assertFalse($manager->isOwner());

        $this->assertFalse($owner->isCashier());
        $this->assertTrue($owner->isManagerOrOwner());
        $this->assertTrue($owner->isOwner());
    }

    public function test_role_middleware_restricts_cashier_from_management_routes(): void
    {
        $cashier = User::factory()->create(['role_id' => 1]);

        // Cashier can access POS checkout and Payment create
        $response = $this->actingAs($cashier)->get('/pos');
        $response->assertStatus(200);

        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'customer_type' => 'Retail',
        ]);
        $account = \App\Models\CreditAccount::create([
            'customer_id' => $customer->id,
            'credit_limit' => 5000,
            'current_balance' => 1000,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($cashier)->get(route('payments.create', $account));
        $response->assertStatus(200);

        // Cashier is forbidden from Staff management, Products modification, and Stock Out
        $response = $this->actingAs($cashier)->get('/users');
        $response->assertStatus(403);

        $response = $this->actingAs($cashier)->get('/products');
        $response->assertStatus(200);

        $response = $this->actingAs($cashier)->get('/products/create');
        $response->assertStatus(403);

        $response = $this->actingAs($cashier)->get('/stock-outs');
        $response->assertStatus(403);
    }

    public function test_role_middleware_restricts_manager_from_owner_routes(): void
    {
        $manager = User::factory()->create(['role_id' => 2]);

        // Manager can access Products, Stock Ins, Customers
        $response = $this->actingAs($manager)->get('/products');
        $response->assertStatus(200);

        $response = $this->actingAs($manager)->get('/stock-ins');
        $response->assertStatus(200);

        // Manager is forbidden from User / Staff management
        $response = $this->actingAs($manager)->get('/users');
        $response->assertStatus(403);
    }

    public function test_role_middleware_allows_owner_full_access(): void
    {
        $owner = User::factory()->create(['role_id' => 3]);

        $response = $this->actingAs($owner)->get('/users');
        $response->assertStatus(200);

        $response = $this->actingAs($owner)->get('/products');
        $response->assertStatus(200);

        $response = $this->actingAs($owner)->get('/pos');
        $response->assertStatus(200);
    }

    public function test_user_name_accessor_and_mutator_backward_compatibility(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'suffix' => 'Jr.',
        ]);

        $this->assertEquals('Juan Dela Cruz Jr.', $user->name);

        // Test mutating name attribute directly
        $user->name = 'Maria Santos Clave';
        $this->assertEquals('Maria', $user->first_name);
        $this->assertEquals('Santos', $user->middle_name);
        $this->assertEquals('Clave', $user->last_name);
        $this->assertEquals('Maria Santos Clave', $user->name);
    }

    public function test_customer_name_accessor_and_mutator_backward_compatibility(): void
    {
        $customer = Customer::create([
            'first_name' => 'Pedro',
            'middle_name' => 'G.',
            'last_name' => 'Penduko',
            'suffix' => null,
            'business_name' => 'Penduko LPG Station',
            'customer_type' => 'Retail',
        ]);

        $this->assertEquals('Pedro G. Penduko', $customer->name);

        // Test mutating customer name
        $customer->name = 'Roberto Carlos Jr.';
        $this->assertEquals('Roberto', $customer->first_name);
        $this->assertEquals('Carlos', $customer->last_name);
        $this->assertEquals('Jr.', $customer->suffix);
        $this->assertEquals('Roberto Carlos Jr.', $customer->name);
    }
}
