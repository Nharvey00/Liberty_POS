<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\StockIn;

class StockInSeeder extends Seeder
{
    public function run(): void
    {
        if (StockIn::count() > 0) {
            $this->command->info('Stock-ins already exist — skipping StockInSeeder.');
            return;
        }

        $product11kg  = Product::where('name', '11kg LPG Cylinder')->first();
        $product22kg  = Product::where('name', '22kg LPG Cylinder')->first();
        $product50kg  = Product::where('name', '50kg LPG Cylinder')->first();
        $product2_7kg = Product::where('name', '2.7kg LPG Cylinder')->first();
        $valve        = Product::where('name', 'POL Valve (Brass)')->first();
        $regulator    = Product::where('name', 'LPG Regulator w/ Hose')->first();

        $deliveries = [
            [
                'product_id'        => $product11kg->id,
                'quantity_received'  => 30,
                'empty_returned_qty' => 5,
                'remarks'            => 'Regular weekly delivery from Petron depot',
                'created_at'         => now()->subDays(28),
            ],
            [
                'product_id'        => $product22kg->id,
                'quantity_received'  => 15,
                'empty_returned_qty' => 3,
                'remarks'            => 'Regular weekly delivery from Petron depot',
                'created_at'         => now()->subDays(28),
            ],
            [
                'product_id'        => $product50kg->id,
                'quantity_received'  => 10,
                'empty_returned_qty' => 2,
                'remarks'            => 'Industrial resupply — Coke/Pepsi demand',
                'created_at'         => now()->subDays(21),
            ],
            [
                'product_id'        => $product2_7kg->id,
                'quantity_received'  => 20,
                'empty_returned_qty' => 0,
                'remarks'            => 'Small cylinder restock',
                'created_at'         => now()->subDays(14),
            ],
            [
                'product_id'        => $valve->id,
                'quantity_received'  => 15,
                'empty_returned_qty' => 0,
                'remarks'            => 'Accessories restock from supplier',
                'created_at'         => now()->subDays(10),
            ],
            [
                'product_id'        => $product11kg->id,
                'quantity_received'  => 25,
                'empty_returned_qty' => 8,
                'remarks'            => 'Mid-month top-up delivery',
                'created_at'         => now()->subDays(7),
            ],
        ];

        foreach ($deliveries as $data) {
            StockIn::create(array_merge($data, ['updated_at' => $data['created_at']]));
        }
    }
}
