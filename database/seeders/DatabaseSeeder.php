<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the 4 Official Roles
        $ownerRole = Role::create(['role_name' => 'Owner']);
        $managerRole = Role::create(['role_name' => 'Operations Manager']);
        $staffRole = Role::create(['role_name' => 'Sales/store staff']);
        $accountingRole = Role::create(['role_name' => 'Accounting']);

        // 2. Create Default Users (Password is 'password123')
        User::create([
            'name' => 'System Owner',
            'email' => 'owner@liberty.com',
            'password' => Hash::make('password123'),
            'role_id' => $ownerRole->id,
        ]);

        User::create([
            'name' => 'Front Desk Cashier',
            'email' => 'cashier@liberty.com',
            'password' => Hash::make('password123'),
            'role_id' => $staffRole->id,
        ]);

        // 3. Create Standard Customers
        Customer::create([
            'name' => 'Walk-in Customer',
            'customer_type' => 'Normal',
        ]);

        Customer::create([
            'name' => 'The Coke Company',
            'business_name' => 'Coca-Cola Beverages Phils',
            'tin_number' => '123-456-789-000',
            'customer_type' => 'Coke Company',
        ]);

        // 4. Create Standard Products
        Product::create([
            'name' => '11kg Gasul SQ',
            'price' => 1000.00,
            'new_cylinder_price' => 2500.00,
            'stock_quantity' => 50,
            'empty_quantity' => 10,
            'standard_capacity_kg' => 11.00
        ]);

        Product::create([
            'name' => '50kg Gasul POL',
            'price' => 4500.00,
            'new_cylinder_price' => 8000.00,
            'stock_quantity' => 20,
            'empty_quantity' => 5,
            'standard_capacity_kg' => 50.00
        ]);
        
        Product::create([
            'name' => 'LPG High Pressure Hose',
            'price' => 250.00,
            'new_cylinder_price' => null, // Accessories don't have this
            'stock_quantity' => 100,
            'empty_quantity' => 0, // Accessories don't have empties
            'standard_capacity_kg' => null // Accessories don't have capacity
        ]);
    }
}