<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\CreditLedger;
use App\Models\CreditAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = CreditAccount::with('customer')->get();

        if ($accounts->isEmpty()) {
            return;
        }

        $paymentAmounts = [500, 800, 1200, 1000, 2000, 1500, 3000, 500];
        $daysAgo        = [28, 22, 17, 14, 10, 7, 4, 1];

        foreach ($accounts->take(8) as $i => $account) {
            $amount = $paymentAmounts[$i] ?? 500;
            $date   = Carbon::now()->subDays($daysAgo[$i] ?? 10);

            DB::transaction(function () use ($account, $amount, $date) {
                // 1. Record the payment
                $payment = Payment::create([
                    'credit_account_id' => $account->id,
                    'amount'            => $amount,
                    'created_at'        => $date,
                ]);

                // 2. Double-entry: create the matching credit ledger entry
                CreditLedger::create([
                    'credit_account_id' => $account->id,
                    'transaction_type'  => 'Payment',
                    'amount'            => $amount,
                    'order_id'          => null,
                    'payment_id'        => $payment->id,
                    'created_at'        => $date,
                ]);
            });
        }
    }
}
