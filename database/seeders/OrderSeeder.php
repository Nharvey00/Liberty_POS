<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\User;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use Carbon\Carbon;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_PH');
        $products = Product::all();
        $customers = Customer::all();
        $adminId = User::first()->id ?? 1;

        $b2bTypes = ['Tertiary', 'MRO', 'Service Station', 'Commercial', 'Main Store'];

        // 1. Establish Credit Accounts for all B2B Classifications (no-op if CreditAccountSeeder already ran)
        foreach ($customers as $customer) {
            if (in_array($customer->customer_type, $b2bTypes)) {
                CreditAccount::firstOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'agreed_monthly_payment' => rand(10000, 50000),
                        'is_active' => true,
                    ]
                );
            }
        }

        // 2. Generate 100 realistic orders
        for ($i = 0; $i < 100; $i++) {
            $customer = $customers->random();

            // PRESENTATION: first 25 orders happen today (dashboard), remaining 75 scattered across the last 90 days (charts)
            if ($i < 25) {
                // Clamp to start of day so "today" holds even when seeding before noon
                $orderDate = Carbon::now()->subMinutes(rand(1, 720))->max(Carbon::today());
            } else {
                $orderDate = Carbon::now()->subDays(rand(1, 90))->setTime(rand(7, 19), rand(0, 59), rand(0, 59));
            }

            // LOGIC: B2B classifications use Credit 75% of the time. Households use Cash.
            // orders.payment_method is strictly 'Cash' or 'Credit' (validated by StoreOrderRequest)
            $paymentMethod = 'Cash';
            if (in_array($customer->customer_type, $b2bTypes) && rand(1, 100) <= 75) {
                $paymentMethod = 'Credit';
            }

            $orderTotal = 0;
            $itemsForThisOrder = [];

            // LOGIC: Match the products to the specific classification
            if (in_array($customer->customer_type, ['Commercial', 'MRO', 'Service Station'])) {
                // Heavy users buy 50kg and 22kg tanks
                $filtered = $products->filter(fn($p) => str_contains($p->name, '50kg') || str_contains($p->name, '22kg'));
                if ($filtered->isEmpty()) $filtered = $products;
                $selectedProducts = collect([$filtered->random()]);
                $qtyMultiplier = rand(3, 8); // Bulk ordering
            } elseif (in_array($customer->customer_type, ['Tertiary', 'Main Store'])) {
                // Eateries & Stores buy 22kg and 11kg tanks
                $filtered = $products->filter(fn($p) => str_contains($p->name, '22kg') || str_contains($p->name, '11kg'));
                if ($filtered->isEmpty()) $filtered = $products;
                $selectedProducts = collect([$filtered->random()]);
                $qtyMultiplier = rand(2, 4);
            } else {
                // Households buy 11kg or smaller, plus accessories
                $filtered = $products->filter(fn($p) => str_contains($p->name, '11kg') || str_contains($p->name, '5kg') || str_contains($p->name, '2.7kg') || $p->standard_capacity_kg === null);
                if ($filtered->isEmpty()) $filtered = $products;
                $selectedProducts = $filtered->random(min(rand(1, 2), $filtered->count()));
                $qtyMultiplier = 1;
            }

            foreach ($selectedProducts as $product) {
                $qty = $qtyMultiplier;

                // Mirror PosController pricing: cylinders are refill swaps (price);
                // accessories are non-swap (new_cylinder_price if set, else price)
                $isSwap = !is_null($product->standard_capacity_kg);
                $unitPrice = (!$isSwap && !is_null($product->new_cylinder_price)) ? $product->new_cylinder_price : $product->price;
                $subtotal = round($unitPrice * $qty, 2);
                $orderTotal += $subtotal;

                // order_items has NO price/unit_price column — it stores the line `subtotal`
                $itemsForThisOrder[] = [
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'is_swap' => $isSwap,
                    'residual_kg' => null,
                    'actual_consumed_kg' => null,
                    'subtotal' => $subtotal,
                ];
            }

            // LOGIC: 15% of Cash-paying Households get a Senior/PWD 5% Discount
            // discount_type matches POS values: 'senior' | 'pwd' | null
            $discountType = null;
            $discountAmount = 0;
            $refName = null;
            $refId = null;

            if ($customer->customer_type === 'Household' && $paymentMethod === 'Cash' && rand(1, 100) <= 15) {
                $discountType = $faker->randomElement(['senior', 'pwd']);
                $discountAmount = round($orderTotal * 0.05, 2);
                $refName = $faker->name();
                $refId = $faker->numerify('OSCA-####-####');
            }

            $finalTotal = max(0, $orderTotal - $discountAmount);

            DB::transaction(function () use ($customer, $adminId, $finalTotal, $discountType, $discountAmount, $refName, $refId, $paymentMethod, $orderDate, $itemsForThisOrder) {
                // 1. Save the Order (timestamps assigned directly; updated_at is not mass-assignable)
                $order = new Order([
                    'customer_id' => $customer->id,
                    'user_id' => $adminId,
                    'total_amount' => $finalTotal,
                    'discount_type' => $discountType,
                    'discount_amount' => $discountAmount,
                    'discount_reference_name' => $refName,
                    'discount_reference_id' => $refId,
                    'senior_id' => $refId,
                    'payment_method' => $paymentMethod,
                    'status' => 'completed',
                ]);
                $order->created_at = $orderDate;
                $order->updated_at = $orderDate;
                $order->save();

                // Same invoice format as PosController (order id guarantees uniqueness)
                $order->invoice_number = 'INV-' . $orderDate->format('Y') . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);
                $order->timestamps = false;
                $order->save();

                // 2. Attach the Items via Eloquent so the is_swap mutator emits pgsql-safe booleans
                foreach ($itemsForThisOrder as $item) {
                    $orderItem = new OrderItem($item + ['order_id' => $order->id]);
                    $orderItem->created_at = $orderDate;
                    $orderItem->updated_at = $orderDate;
                    $orderItem->save();
                }

                // 3. Update the Credit Ledger if the payment was Utang
                if ($paymentMethod === 'Credit') {
                    $creditAccount = CreditAccount::where('customer_id', $customer->id)->first();
                    if ($creditAccount) {
                        $ledger = new CreditLedger([
                            'credit_account_id' => $creditAccount->id,
                            'order_id' => $order->id,
                            'transaction_type' => 'Charge',
                            'amount' => $finalTotal,
                        ]);
                        $ledger->created_at = $orderDate;
                        $ledger->updated_at = $orderDate;
                        $ledger->save();
                    }
                }
            });
        }
    }
}