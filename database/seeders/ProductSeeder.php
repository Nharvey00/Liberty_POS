<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // All columns below exist in the Dev 3 migrations (source of truth).
        // new_cylinder_price = price of the empty tank itself (null for accessories).
        // empty_quantity = how many empty cylinders are currently held.
        // standard_capacity_kg = used ONLY for Company (residual-weight) billing logic.
        $products = [
            [
                'name'                 => '11kg LPG Cylinder',
                'price'                => 950.00,
                'new_cylinder_price'   => 1500.00,
                'stock_quantity'       => 45,
                'empty_quantity'       => 12,
                'standard_capacity_kg' => 11.00,
            ],
            [
                'name'                 => '22kg LPG Cylinder',
                'price'                => 1800.00,
                'new_cylinder_price'   => 2200.00,
                'stock_quantity'       => 25,
                'empty_quantity'       => 8,
                'standard_capacity_kg' => 22.00,
            ],
            [
                'name'                 => '50kg Industrial Cylinder',
                'price'                => 4100.00,
                'new_cylinder_price'   => 4000.00,
                'stock_quantity'       => 10,
                'empty_quantity'       => 3,
                'standard_capacity_kg' => 50.00,
            ],
            [
                'name'                 => '2.7kg LPG Cylinder',
                'price'                => 250.00,
                'new_cylinder_price'   => 600.00,
                'stock_quantity'       => 30,
                'empty_quantity'       => 5,
                'standard_capacity_kg' => 2.70,
            ],
            [
                'name'                 => 'POL Valve (Brass)',
                'price'                => 320.00,
                'new_cylinder_price'   => null,  // Accessory — no cylinder swap
                'stock_quantity'       => 40,
                'empty_quantity'       => 0,
                'standard_capacity_kg' => null,  // Not an LPG cylinder — no KG billing
            ],
            [
                'name'                 => 'LPG Regulator w/ Hose',
                'price'                => 480.00,
                'new_cylinder_price'   => null,  // Accessory — no cylinder swap
                'stock_quantity'       => 20,
                'empty_quantity'       => 0,
                'standard_capacity_kg' => null,  // Not an LPG cylinder — no KG billing
            ],
        ];

        foreach ($products as $data) {
            Product::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
