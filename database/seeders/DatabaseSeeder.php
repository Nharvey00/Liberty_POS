<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CreditAccount;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $role1 = Role::create(['role_name' => 'Level 1']);
        $role2 = Role::create(['role_name' => 'Level 2']);
        $roleOwner = Role::create(['role_name' => 'Level 3']);

        // Create Admin User
        User::factory()->create([
            'name' => 'Admin Owner',
            'email' => 'admin@libertylpg.com',
            'password' => bcrypt('password'),
            'role_id' => $roleOwner->id,
        ]);

        // Create Products
        Product::create([
            'name' => '11kg LPG Cylinder',
            'selling_price' => 950.00,
            'stock_quantity' => 50,
            'empty_quantity' => 10,
        ]);

        Product::create([
            'name' => '22kg LPG Cylinder',
            'selling_price' => 1800.00,
            'stock_quantity' => 20,
            'empty_quantity' => 5,
        ]);

        // Create Normal Customer
        $normal = Customer::create([
            'name' => 'Juan Dela Cruz',
            'customer_type' => 'Normal',
            'phone' => '09123456789',
            'address' => 'Catalunan Grande',
        ]);
        
        CreditAccount::create(['customer_id' => $normal->id]);

        // Create Company Customer (Replacing 'Coke Company' type with 'Company')
        $company = Customer::create([
            'name' => 'Procurement Officer',
            'customer_type' => 'Company',
            'business_name' => 'Coke Company',
            'tin_number' => '123-456-789-000',
            'phone' => '09987654321',
            'address' => 'Catalunan Industrial Park',
        ]);

        CreditAccount::create(['customer_id' => $company->id]);
    }
}