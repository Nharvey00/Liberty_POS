<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // --- 20 LPG Cylinders (Different Brands & Sizes) ---
            // Liberty LPG
            ['name' => '11kg Liberty LPG Cylinder', 'price' => 950.00, 'new_cylinder_price' => 1500.00, 'stock_quantity' => 45, 'empty_quantity' => 12, 'standard_capacity_kg' => 11.00],
            ['name' => '22kg Liberty LPG Cylinder', 'price' => 1800.00, 'new_cylinder_price' => 2200.00, 'stock_quantity' => 25, 'empty_quantity' => 8, 'standard_capacity_kg' => 22.00],
            ['name' => '50kg Liberty Industrial', 'price' => 4100.00, 'new_cylinder_price' => 4000.00, 'stock_quantity' => 10, 'empty_quantity' => 3, 'standard_capacity_kg' => 50.00],
            ['name' => '2.7kg Liberty LPG Cylinder', 'price' => 250.00, 'new_cylinder_price' => 600.00, 'stock_quantity' => 30, 'empty_quantity' => 5, 'standard_capacity_kg' => 2.70],
            ['name' => '5kg Liberty LPG Cylinder', 'price' => 480.00, 'new_cylinder_price' => 950.00, 'stock_quantity' => 15, 'empty_quantity' => 4, 'standard_capacity_kg' => 5.00],

            // Petron Gasul
            ['name' => '11kg Petron Gasul', 'price' => 980.00, 'new_cylinder_price' => 1550.00, 'stock_quantity' => 40, 'empty_quantity' => 15, 'standard_capacity_kg' => 11.00],
            ['name' => '22kg Petron Gasul', 'price' => 1850.00, 'new_cylinder_price' => 2250.00, 'stock_quantity' => 20, 'empty_quantity' => 10, 'standard_capacity_kg' => 22.00],
            ['name' => '50kg Petron Industrial', 'price' => 4200.00, 'new_cylinder_price' => 4100.00, 'stock_quantity' => 15, 'empty_quantity' => 5, 'standard_capacity_kg' => 50.00],
            ['name' => '2.7kg Petron Gasulito', 'price' => 260.00, 'new_cylinder_price' => 620.00, 'stock_quantity' => 25, 'empty_quantity' => 8, 'standard_capacity_kg' => 2.70],
            ['name' => '7kg Petron Gasul', 'price' => 650.00, 'new_cylinder_price' => 1100.00, 'stock_quantity' => 10, 'empty_quantity' => 2, 'standard_capacity_kg' => 7.00],

            // Solane
            ['name' => '11kg Solane', 'price' => 960.00, 'new_cylinder_price' => 1520.00, 'stock_quantity' => 35, 'empty_quantity' => 10, 'standard_capacity_kg' => 11.00],
            ['name' => '22kg Solane', 'price' => 1820.00, 'new_cylinder_price' => 2220.00, 'stock_quantity' => 18, 'empty_quantity' => 6, 'standard_capacity_kg' => 22.00],
            ['name' => '50kg Solane Industrial', 'price' => 4150.00, 'new_cylinder_price' => 4050.00, 'stock_quantity' => 12, 'empty_quantity' => 4, 'standard_capacity_kg' => 50.00],
            ['name' => '1.4kg Solane Mini', 'price' => 150.00, 'new_cylinder_price' => 450.00, 'stock_quantity' => 20, 'empty_quantity' => 5, 'standard_capacity_kg' => 1.40],
            ['name' => '5kg Solane', 'price' => 470.00, 'new_cylinder_price' => 900.00, 'stock_quantity' => 15, 'empty_quantity' => 3, 'standard_capacity_kg' => 5.00],

            // Phoenix & Prycegas
            ['name' => '11kg Phoenix Super LPG', 'price' => 940.00, 'new_cylinder_price' => 1480.00, 'stock_quantity' => 50, 'empty_quantity' => 20, 'standard_capacity_kg' => 11.00],
            ['name' => '22kg Phoenix Super LPG', 'price' => 1780.00, 'new_cylinder_price' => 2180.00, 'stock_quantity' => 22, 'empty_quantity' => 7, 'standard_capacity_kg' => 22.00],
            ['name' => '50kg Phoenix Industrial', 'price' => 4050.00, 'new_cylinder_price' => 3950.00, 'stock_quantity' => 8, 'empty_quantity' => 2, 'standard_capacity_kg' => 50.00],
            ['name' => '11kg Prycegas', 'price' => 930.00, 'new_cylinder_price' => 1450.00, 'stock_quantity' => 30, 'empty_quantity' => 12, 'standard_capacity_kg' => 11.00],
            ['name' => '5kg Prycegas Power', 'price' => 460.00, 'new_cylinder_price' => 920.00, 'stock_quantity' => 18, 'empty_quantity' => 6, 'standard_capacity_kg' => 5.00],

            // --- 10 ACCESSORIES (No cylinder swap logic, standard_capacity_kg = null) ---
            ['name' => 'POL Valve (Brass)', 'price' => 320.00, 'new_cylinder_price' => null, 'stock_quantity' => 40, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'Snap-on Regulator (De-roskas)', 'price' => 280.00, 'new_cylinder_price' => null, 'stock_quantity' => 35, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'LPG Regulator w/ Hose & Clamps', 'price' => 480.00, 'new_cylinder_price' => null, 'stock_quantity' => 20, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'High Pressure Regulator (Industrial)', 'price' => 850.00, 'new_cylinder_price' => null, 'stock_quantity' => 10, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'Standard LPG Hose (1.5 meters)', 'price' => 150.00, 'new_cylinder_price' => null, 'stock_quantity' => 60, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'High Pressure Hose (Per meter)', 'price' => 120.00, 'new_cylinder_price' => null, 'stock_quantity' => 100, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'Steel Hose Clamps (Pair)', 'price' => 30.00, 'new_cylinder_price' => null, 'stock_quantity' => 150, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'Gas Stove Burner Head', 'price' => 180.00, 'new_cylinder_price' => null, 'stock_quantity' => 25, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'Teflon Tape (Gas Grade)', 'price' => 25.00, 'new_cylinder_price' => null, 'stock_quantity' => 80, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
            ['name' => 'LPG Cylinder Trolley/Stand', 'price' => 350.00, 'new_cylinder_price' => null, 'stock_quantity' => 15, 'empty_quantity' => 0, 'standard_capacity_kg' => null],
        ];

        foreach ($products as $data) {
            Product::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}