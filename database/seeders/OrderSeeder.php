<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\CreditLedger;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $admin    = User::where('email', 'admin@libertylpg.com')->first();
        $cashier1 = User::where('email', 'cashier1@libertylpg.com')->first();

        $cylinders   = Product::whereNotNull('standard_capacity_kg')->get();
        $accessories = Product::whereNull('standard_capacity_kg')->get();

        $normalCustomers  = Customer::where('customer_type', 'Normal')->get();
        $companyCustomers = Customer::where('customer_type', 'Company')->get();

        // --- 5 Walk-in Cash Orders (no customer, cash only) ---
        $walkInProducts = $cylinders->merge($accessories)->all();
        for ($i = 0; $i < 5; $i++) {
            $product = $walkInProducts[array_rand($walkInProducts)];
            $qty     = rand(1, 2);
            $subtotal = $product->price * $qty;

            $order = Order::create([
                'customer_id'     => null,
                'user_id'         => $admin->id,
                'payment_method'  => 'Cash',
                'total_amount'    => $subtotal,
                'discount_amount' => 0,
                'created_at'      => Carbon::now()->subDays(rand(1, 30)),
            ]);

            OrderItem::create([
                'order_id'           => $order->id,
                'product_id'         => $product->id,
                'quantity'           => $qty,
                'is_swap'            => $product->new_cylinder_price !== null, // Accessories never swap
                'residual_kg'        => null,
                'actual_consumed_kg' => null,
                'subtotal'           => $subtotal,
            ]);
        }

        // --- 5 Normal Customer Orders (Mix of Cash and Credit, standard pricing) ---
        foreach ($normalCustomers->take(5) as $customer) {
            $product       = $cylinders->random();
            $qty           = rand(1, 3);
            $subtotal      = $product->price * $qty;
            $paymentMethod = rand(0, 1) ? 'Cash' : 'Credit';
            $date          = Carbon::now()->subDays(rand(1, 30));

            $order = Order::create([
                'customer_id'     => $customer->id,
                'user_id'         => $cashier1->id,
                'payment_method'  => $paymentMethod,
                'total_amount'    => $subtotal,
                'discount_amount' => 0,
                'created_at'      => $date,
            ]);

            OrderItem::create([
                'order_id'           => $order->id,
                'product_id'         => $product->id,
                'quantity'           => $qty,
                'is_swap'            => true,
                'residual_kg'        => null,
                'actual_consumed_kg' => null,
                'subtotal'           => $subtotal,
            ]);

            // Auto-post a Charge to the credit ledger if paying by Credit
            if ($paymentMethod === 'Credit') {
                $creditAccount = $customer->creditAccount;
                if ($creditAccount) {
                    CreditLedger::create([
                        'credit_account_id' => $creditAccount->id,
                        'order_id'          => $order->id,
                        'transaction_type'  => 'Charge',
                        'amount'            => $subtotal,
                        'created_at'        => $date,
                    ]);
                }
            }
        }

        // --- 5 Company Orders (KG-based billing using residual weight math) ---
        $largeCylinders = $cylinders->where('standard_capacity_kg', '>=', 22)->values();
        if ($largeCylinders->isEmpty()) {
            $largeCylinders = $cylinders;
        }

        foreach ($companyCustomers->take(5) as $customer) {
            $product       = $largeCylinders->random();
            $qty           = rand(1, 3);
            $residualKg    = round(rand(100, 500) / 100, 2); // e.g. 2.47 kg
            $actualConsumed = max(0, $product->standard_capacity_kg - $residualKg);
            $pricePerKg     = $product->price / $product->standard_capacity_kg;
            $subtotal       = $actualConsumed * $pricePerKg * $qty;
            $paymentMethod  = rand(0, 1) ? 'Cash' : 'Credit';
            $date           = Carbon::now()->subDays(rand(1, 30));

            $order = Order::create([
                'customer_id'     => $customer->id,
                'user_id'         => $admin->id,
                'payment_method'  => $paymentMethod,
                'total_amount'    => round($subtotal, 2),
                'discount_amount' => 0,
                'created_at'      => $date,
            ]);

            OrderItem::create([
                'order_id'           => $order->id,
                'product_id'         => $product->id,
                'quantity'           => $qty,
                'is_swap'            => true,
                'residual_kg'        => $residualKg,
                'actual_consumed_kg' => round($actualConsumed, 4),
                'subtotal'           => round($subtotal, 2),
            ]);

            if ($paymentMethod === 'Credit') {
                $creditAccount = $customer->creditAccount;
                if ($creditAccount) {
                    CreditLedger::create([
                        'credit_account_id' => $creditAccount->id,
                        'order_id'          => $order->id,
                        'transaction_type'  => 'Charge',
                        'amount'            => round($subtotal, 2),
                        'created_at'        => $date,
                    ]);
                }
            }
        }
    }
}
