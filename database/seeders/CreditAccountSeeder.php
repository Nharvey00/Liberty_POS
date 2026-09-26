<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CreditAccount;
use App\Models\Customer;

class CreditAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Grant credit accounts to all seeded customers.
        // Company customers get higher agreed monthly payments.
        $normalMonthlyPayment   = 1000.00;
        $companyMonthlyPayment  = 5000.00;

        $customers = Customer::all();

        foreach ($customers as $customer) {
            // Skip if already has a credit account (safe to re-run)
            if ($customer->creditAccount()->exists()) {
                continue;
            }

            CreditAccount::create([
                'customer_id'            => $customer->id,
                'agreed_monthly_payment' => $customer->customer_type === 'Company'
                    ? $companyMonthlyPayment
                    : $normalMonthlyPayment,
                'is_active'              => true,
            ]);
        }
    }
}
