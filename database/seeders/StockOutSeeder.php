<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockOut;
use App\Models\Product;
use Carbon\Carbon;

class StockOutSeeder extends Seeder
{
    public function run(): void
    {
        $cylinders = Product::whereNotNull('standard_capacity_kg')->get();

        $adjustments = [
            ['days_ago' => 20, 'qty_removed' => 2, 'empty_removed' => 0, 'reason' => 'Damaged cylinder — condemned'],
            ['days_ago' => 12, 'qty_removed' => 1, 'empty_removed' => 0, 'reason' => 'Cylinder returned to supplier (faulty valve)'],
            ['days_ago' =>  5, 'qty_removed' => 3, 'empty_removed' => 1, 'reason' => 'Inventory adjustment — recount discrepancy'],
        ];

        foreach ($adjustments as $i => $adj) {
            $product = $cylinders->values()[$i % $cylinders->count()];

            StockOut::create([
                'product_id'             => $product->id,
                'quantity_removed'       => $adj['qty_removed'],
                'empty_quantity_removed' => $adj['empty_removed'],
                'reason'                 => $adj['reason'],
                'created_at'             => Carbon::now()->subDays($adj['days_ago']),
            ]);
        }
    }
}
