<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StockIn;
use App\Models\Product;
use Carbon\Carbon;

class StockInSeeder extends Seeder
{
    public function run(): void
    {
        $cylinders = Product::whereNotNull('standard_capacity_kg')->get();

        $deliveries = [
            ['days_ago' => 25, 'qty_received' => 20, 'empty_returned_qty' => 15, 'remarks' => 'Regular Petron delivery'],
            ['days_ago' => 18, 'qty_received' => 30, 'empty_returned_qty' => 20, 'remarks' => 'Petron — top-up order'],
            ['days_ago' => 14, 'qty_received' => 15, 'empty_returned_qty' => 10, 'remarks' => 'Solane delivery'],
            ['days_ago' =>  7, 'qty_received' => 25, 'empty_returned_qty' => 18, 'remarks' => 'Regular Petron delivery'],
            ['days_ago' =>  3, 'qty_received' => 10, 'empty_returned_qty' =>  5, 'remarks' => 'Emergency top-up'],
            ['days_ago' =>  1, 'qty_received' => 40, 'empty_returned_qty' => 30, 'remarks' => 'Weekly bulk delivery'],
        ];

        foreach ($deliveries as $i => $delivery) {
            $product = $cylinders->values()[$i % $cylinders->count()];

            StockIn::create([
                'product_id'        => $product->id,
                'quantity_received' => $delivery['qty_received'],
                'empty_returned_qty' => $delivery['empty_returned_qty'],
                'remarks'           => $delivery['remarks'],
                'created_at'        => Carbon::now()->subDays($delivery['days_ago']),
            ]);
        }
    }
}
