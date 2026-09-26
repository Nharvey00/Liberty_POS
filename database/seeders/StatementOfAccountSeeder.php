<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StatementOfAccount;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use Carbon\Carbon;

class StatementOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = CreditAccount::with('customer')->get();

        if ($accounts->isEmpty()) {
            return;
        }

        $billingPeriods = [
            [
                'start'    => Carbon::now()->startOfMonth()->subMonth(),
                'end'      => Carbon::now()->endOfMonth()->subMonth(),
                'label'    => 'Previous Month',
            ],
            [
                'start'    => Carbon::now()->startOfMonth(),
                'end'      => Carbon::now(),
                'label'    => 'Current Month (partial)',
            ],
        ];

        // Generate SOAs for the first 2 accounts across 2 billing periods
        foreach ($accounts->take(2) as $account) {
            foreach ($billingPeriods as $period) {
                $end = Carbon::parse($period['end'])->endOfDay();

                // Use the carry-over formula (matches StatementController::store() bug fix)
                $totalCharges = CreditLedger::where('credit_account_id', $account->id)
                    ->where('transaction_type', 'Charge')
                    ->where('created_at', '<=', $end)
                    ->sum('amount');

                $totalPayments = CreditLedger::where('credit_account_id', $account->id)
                    ->where('transaction_type', 'Payment')
                    ->where('created_at', '<=', $end)
                    ->sum('amount');

                $totalDue = max($totalCharges - $totalPayments, 0);

                StatementOfAccount::create([
                    'credit_account_id'    => $account->id,
                    'billing_period_start' => $period['start']->toDateString(),
                    'billing_period_end'   => $period['end']->toDateString(),
                    'total_due'            => $totalDue,
                    'is_paid'              => $totalDue == 0,
                ]);
            }
        }
    }
}
