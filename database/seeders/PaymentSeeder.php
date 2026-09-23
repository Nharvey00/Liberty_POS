<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        if (Payment::count() > 0) {
            $this->command->info('Payments already exist — skipping PaymentSeeder.');
            return;
        }

        $payments = [
            // Juan Dela Cruz — partial payments toward his utang
            ['customer' => 'Juan Dela Cruz',                            'amount' => 1000.00, 'days_ago' => 18],
            ['customer' => 'Juan Dela Cruz',                            'amount' => 500.00,  'days_ago' => 10],

            // Maria Garcia — partial payments
            ['customer' => 'Maria Garcia',                              'amount' => 1500.00, 'days_ago' => 16],
            ['customer' => 'Maria Garcia',                              'amount' => 1000.00, 'days_ago' => 5],

            // Coca-Cola (Engr. Rodel Pascual) — large company payments
            ['customer' => 'Engr. Rodel Pascual - Coca-Cola Beverages', 'amount' => 10000.00, 'days_ago' => 15],
            ['customer' => 'Engr. Rodel Pascual - Coca-Cola Beverages', 'amount' => 5000.00,  'days_ago' => 3],

            // Pepsi (Grace Lim) — company payment
            ['customer' => 'Grace Lim - Pepsi-Cola Products',           'amount' => 8000.00, 'days_ago' => 11],

            // Lorna Villanueva — final clearing payment from old balance
            ['customer' => 'Lorna Villanueva',                          'amount' => 950.00,  'days_ago' => 22],
        ];

        foreach ($payments as $data) {
            $customer = Customer::where('name', $data['customer'])->first();
            if (!$customer) {
                continue;
            }

            $creditAccount = CreditAccount::where('customer_id', $customer->id)->first();
            if (!$creditAccount) {
                continue;
            }

            $date = now()->subDays($data['days_ago']);

            // Double-entry: create Payment AND matching CreditLedger entry atomically
            DB::transaction(function () use ($creditAccount, $data, $date) {
                $payment = Payment::create([
                    'credit_account_id' => $creditAccount->id,
                    'amount'            => $data['amount'],
                    'created_at'        => $date,
                    'updated_at'        => $date,
                ]);

                CreditLedger::create([
                    'credit_account_id' => $creditAccount->id,
                    'transaction_type'  => 'Payment',
                    'amount'            => $data['amount'],
                    'order_id'          => null,
                    'payment_id'        => $payment->id,
                    'created_at'        => $date,
                    'updated_at'        => $date,
                ]);
            });
        }
    }
}
