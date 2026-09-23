<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // NOTE: Live Supabase DB only has: id, name, price, stock_quantity, standard_capacity_kg, timestamps
        // Columns new_cylinder_price and empty_quantity do NOT exist in the live schema.
        $products = [
            [
                'name'                 => '11kg LPG Cylinder',
                'price'                => 950.00,
                'stock_quantity'       => 45,
                'standard_capacity_kg' => 11.00,
            ],
            [
                'name'                 => '22kg LPG Cylinder',
                'price'                => 1800.00,
                'stock_quantity'       => 25,
                'standard_capacity_kg' => 22.00,
            ],
            [
                'name'                 => '50kg LPG Cylinder',
                'price'                => 4100.00,
                'stock_quantity'       => 10,
                'standard_capacity_kg' => 50.00,
            ],
            [
                'name'                 => '2.7kg LPG Cylinder',
                'price'                => 250.00,
                'stock_quantity'       => 30,
                'standard_capacity_kg' => 2.70,
            ],
            [
                'name'                 => 'POL Valve (Brass)',
                'price'                => 320.00,
                'stock_quantity'       => 40,
                'standard_capacity_kg' => null,
            ],
            [
                'name'                 => 'LPG Regulator w/ Hose',
                'price'                => 480.00,
                'stock_quantity'       => 20,
                'standard_capacity_kg' => null,
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
