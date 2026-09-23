<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatementOfAccount;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use Carbon\Carbon;

class StatementOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (StatementOfAccount::count() > 0) {
            $this->command->info('Statements already exist — skipping StatementOfAccountSeeder.');
            return;
        }

        // Current billing period: start of this month to end of this month
        $currentStart = Carbon::now()->startOfMonth();
        $currentEnd   = Carbon::now()->endOfMonth();

        // Previous billing period
        $prevStart = Carbon::now()->subMonth()->startOfMonth();
        $prevEnd   = Carbon::now()->subMonth()->endOfMonth();

        $statements = [
            // Juan — previous month (partially paid)
            [
                'customer' => 'Juan Dela Cruz',
                'start'    => $prevStart,
                'end'      => $prevEnd,
                'is_paid'  => false,
            ],
            // Maria — previous month (not yet paid)
            [
                'customer' => 'Maria Garcia',
                'start'    => $prevStart,
                'end'      => $prevEnd,
                'is_paid'  => false,
            ],
            // Coca-Cola — previous month (paid in full)
            [
                'customer' => 'Engr. Rodel Pascual - Coca-Cola Beverages',
                'start'    => $prevStart,
                'end'      => $prevEnd,
                'is_paid'  => true,
            ],
            // Pepsi — current month (in progress)
            [
                'customer' => 'Grace Lim - Pepsi-Cola Products',
                'start'    => $currentStart,
                'end'      => $currentEnd,
                'is_paid'  => false,
            ],
        ];

        foreach ($statements as $data) {
            $customer = Customer::where('name', $data['customer'])->first();
            if (!$customer) {
                continue;
            }

            $creditAccount = CreditAccount::where('customer_id', $customer->id)->first();
            if (!$creditAccount) {
                continue;
            }

            // Calculate total_due: sum of Charges minus sum of Payments within the billing period
            $totalCharges = CreditLedger::where('credit_account_id', $creditAccount->id)
                ->where('transaction_type', 'Charge')
                ->whereBetween('created_at', [$data['start'], $data['end']->copy()->endOfDay()])
                ->sum('amount');

            $totalPayments = CreditLedger::where('credit_account_id', $creditAccount->id)
                ->where('transaction_type', 'Payment')
                ->whereBetween('created_at', [$data['start'], $data['end']->copy()->endOfDay()])
                ->sum('amount');

            $totalDue = max($totalCharges - $totalPayments, 0);

            StatementOfAccount::create([
                'credit_account_id'    => $creditAccount->id,
                'billing_period_start' => $data['start']->toDateString(),
                'billing_period_end'   => $data['end']->toDateString(),
                'total_due'            => $totalDue,
                'is_paid'              => $data['is_paid'],
            ]);
        }
    }
}
