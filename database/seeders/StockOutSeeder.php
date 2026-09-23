<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\StockOut;

class StockOutSeeder extends Seeder
{
    public function run(): void
    {
        if (StockOut::count() > 0) {
            $this->command->info('Stock-outs already exist — skipping StockOutSeeder.');
            return;
        }

        $product11kg = Product::where('name', '11kg LPG Cylinder')->first();
        $product22kg = Product::where('name', '22kg LPG Cylinder')->first();
        $regulator   = Product::where('name', 'LPG Regulator w/ Hose')->first();

        $adjustments = [
            [
                'product_id'             => $product11kg->id,
                'quantity_removed'       => 2,
                'empty_quantity_removed'  => 0,
                'reason'                 => 'Damaged during delivery — dented valve',
                'created_at'             => now()->subDays(20),
            ],
            [
                'product_id'             => $product22kg->id,
                'quantity_removed'       => 1,
                'empty_quantity_removed'  => 1,
                'reason'                 => 'Leaking cylinder returned to supplier for warranty',
                'created_at'             => now()->subDays(12),
            ],
            [
                'product_id'             => $regulator->id,
                'quantity_removed'       => 1,
                'empty_quantity_removed'  => 0,
                'reason'                 => 'Defective unit — cracked pressure gauge',
                'created_at'             => now()->subDays(5),
            ],
        ];

        foreach ($adjustments as $data) {
            StockOut::create(array_merge($data, ['updated_at' => $data['created_at']]));
        }
    }
}
