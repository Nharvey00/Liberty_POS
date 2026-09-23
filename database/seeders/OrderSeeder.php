<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * NOTE: Live Supabase DB schema differences from migration files:
     * - orders: does NOT have discount_amount column
     * - order_items: does NOT have is_swap column
     * Seeders only use columns that actually exist in the live DB.
     */
    public function run(): void
    {
        // Abort if orders already exist to prevent duplicate data
        if (Order::count() > 0) {
            $this->command->info('Orders already exist — skipping OrderSeeder.');
            return;
        }

        $cashier1 = User::where('email', 'cashier1@libertylpg.com')->first();
        $cashier2 = User::where('email', 'cashier2@libertylpg.com')->first();

        $product11kg  = Product::where('name', '11kg LPG Cylinder')->first();
        $product22kg  = Product::where('name', '22kg LPG Cylinder')->first();
        $product50kg  = Product::where('name', '50kg LPG Cylinder')->first();
        $product2_7kg = Product::where('name', '2.7kg LPG Cylinder')->first();
        $valve        = Product::where('name', 'POL Valve (Brass)')->first();
        $regulator    = Product::where('name', 'LPG Regulator w/ Hose')->first();

        $baseDate = Carbon::now()->subDays(30);

        // ───────────────────────────────────────────────────────
        // CASH SALES (Normal customers — swap cylinders)
        // ───────────────────────────────────────────────────────

        // Order 1: Juan — 2x 11kg (Cash)
        $this->createOrder(
            customer: 'Juan Dela Cruz',
            user: $cashier1,
            paymentMethod: 'Cash',
            date: $baseDate->copy()->addDays(1),
            items: [
                ['product' => $product11kg, 'quantity' => 2],
            ]
        );

        // Order 2: Pedro — 1x 22kg (Cash)
        $this->createOrder(
            customer: 'Pedro Bautista',
            user: $cashier1,
            paymentMethod: 'Cash',
            date: $baseDate->copy()->addDays(2),
            items: [
                ['product' => $product22kg, 'quantity' => 1],
            ]
        );

        // Order 3: Roberto — 3x 2.7kg + 1x regulator (Cash)
        $this->createOrder(
            customer: 'Roberto Tan',
            user: $cashier2,
            paymentMethod: 'Cash',
            date: $baseDate->copy()->addDays(3),
            items: [
                ['product' => $product2_7kg, 'quantity' => 3],
                ['product' => $regulator,    'quantity' => 1],
            ]
        );

        // Order 4: Pedro — 1x 11kg (Cash)
        $this->createOrder(
            customer: 'Pedro Bautista',
            user: $cashier1,
            paymentMethod: 'Cash',
            date: $baseDate->copy()->addDays(5),
            items: [
                ['product' => $product11kg, 'quantity' => 1],
            ]
        );

        // Order 5: Lorna — 1x 11kg (Cash)
        $this->createOrder(
            customer: 'Lorna Villanueva',
            user: $cashier2,
            paymentMethod: 'Cash',
            date: $baseDate->copy()->addDays(7),
            items: [
                ['product' => $product11kg, 'quantity' => 1],
            ]
        );

        // ───────────────────────────────────────────────────────
        // CREDIT SALES — Normal customers (utang)
        // ───────────────────────────────────────────────────────

        // Order 6: Juan — 1x 22kg (Credit) → Charge to ledger
        $this->createOrder(
            customer: 'Juan Dela Cruz',
            user: $cashier1,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(8),
            items: [
                ['product' => $product22kg, 'quantity' => 1],
            ]
        );

        // Order 7: Maria — 2x 11kg (Credit)
        $this->createOrder(
            customer: 'Maria Garcia',
            user: $cashier2,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(9),
            items: [
                ['product' => $product11kg, 'quantity' => 2],
            ]
        );

        // Order 8: Juan — 1x 11kg + 1x valve (Credit)
        $this->createOrder(
            customer: 'Juan Dela Cruz',
            user: $cashier1,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(12),
            items: [
                ['product' => $product11kg, 'quantity' => 1],
                ['product' => $valve,       'quantity' => 1],
            ]
        );

        // Order 9: Maria — 1x 22kg (Credit)
        $this->createOrder(
            customer: 'Maria Garcia',
            user: $cashier1,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(15),
            items: [
                ['product' => $product22kg, 'quantity' => 1],
            ]
        );

        // ───────────────────────────────────────────────────────
        // CREDIT SALES — Company customers (Coke residual logic)
        // ───────────────────────────────────────────────────────

        // Order 10: Coca-Cola — 5x 50kg (Credit, with residual_kg)
        $this->createOrder(
            customer: 'Engr. Rodel Pascual - Coca-Cola Beverages',
            user: $cashier1,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(4),
            items: [
                ['product' => $product50kg, 'quantity' => 5, 'residual_kg' => 3.50, 'standard_capacity_kg' => 50.00],
            ]
        );

        // Order 11: Coca-Cola — 3x 22kg (Credit, residual)
        $this->createOrder(
            customer: 'Engr. Rodel Pascual - Coca-Cola Beverages',
            user: $cashier2,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(10),
            items: [
                ['product' => $product22kg, 'quantity' => 3, 'residual_kg' => 2.00, 'standard_capacity_kg' => 22.00],
            ]
        );

        // Order 12: Pepsi — 4x 50kg (Credit, residual)
        $this->createOrder(
            customer: 'Grace Lim - Pepsi-Cola Products',
            user: $cashier1,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(6),
            items: [
                ['product' => $product50kg, 'quantity' => 4, 'residual_kg' => 5.20, 'standard_capacity_kg' => 50.00],
            ]
        );

        // Order 13: Pepsi — 2x 22kg (Credit, residual)
        $this->createOrder(
            customer: 'Grace Lim - Pepsi-Cola Products',
            user: $cashier2,
            paymentMethod: 'Credit',
            date: $baseDate->copy()->addDays(14),
            items: [
                ['product' => $product22kg, 'quantity' => 2, 'residual_kg' => 1.50, 'standard_capacity_kg' => 22.00],
            ]
        );

        // ───────────────────────────────────────────────────────
        // MORE RECENT CASH SALES
        // ───────────────────────────────────────────────────────

        // Order 14: Juan — 1x 2.7kg (Cash)
        $this->createOrder(
            customer: 'Juan Dela Cruz',
            user: $cashier2,
            paymentMethod: 'Cash',
            date: $baseDate->copy()->addDays(20),
            items: [
                ['product' => $product2_7kg, 'quantity' => 1],
            ]
        );

        // Order 15: Maria — 1x 11kg (Cash)
        $this->createOrder(
            customer: 'Maria Garcia',
            user: $cashier1,
            paymentMethod: 'Cash',
            date: $baseDate->copy()->addDays(25),
            items: [
                ['product' => $product11kg, 'quantity' => 1],
            ]
        );
    }

    /**
     * Helper to create an order with its items and optional credit ledger charge.
     */
    private function createOrder(
        string $customer,
        User $user,
        string $paymentMethod,
        Carbon $date,
        array $items
    ): void {
        $customerModel = Customer::where('name', $customer)->first();
        if (!$customerModel) {
            return;
        }

        // Calculate total from items
        $total = 0;
        foreach ($items as $item) {
            $subtotal = $item['product']->price * $item['quantity'];
            $total += $subtotal;
        }

        $order = Order::create([
            'customer_id'    => $customerModel->id,
            'user_id'        => $user->id,
            'total_amount'   => $total,
            'payment_method' => $paymentMethod,
            'created_at'     => $date,
            'updated_at'     => $date,
        ]);

        // Create order items
        foreach ($items as $item) {
            $subtotal = $item['product']->price * $item['quantity'];

            $orderItemData = [
                'order_id'   => $order->id,
                'product_id' => $item['product']->id,
                'quantity'   => $item['quantity'],
                'subtotal'   => $subtotal,
                'created_at' => $date,
                'updated_at' => $date,
            ];

            // Coke Company residual logic (these columns DO exist in live DB)
            if (isset($item['residual_kg'])) {
                $orderItemData['residual_kg']        = $item['residual_kg'];
                $orderItemData['actual_consumed_kg'] = $item['standard_capacity_kg'] - $item['residual_kg'];
            }

            OrderItem::create($orderItemData);
        }

        // If Credit sale, push a "Charge" into the credit ledger
        if ($paymentMethod === 'Credit') {
            $creditAccount = CreditAccount::where('customer_id', $customerModel->id)->first();

            if ($creditAccount) {
                CreditLedger::create([
                    'credit_account_id' => $creditAccount->id,
                    'transaction_type'  => 'Charge',
                    'amount'            => $total,
                    'order_id'          => $order->id,
                    'payment_id'        => null,
                    'created_at'        => $date,
                    'updated_at'        => $date,
                ]);
            }
        }
    }
}
