<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\CreditAccount;

class CreditAccountSeeder extends Seeder
{
    public function run(): void
    {
        // Give credit accounts to 5 customers (3 normal + 2 company)
        $creditCustomers = [
            // Normal customers with credit (utang)
            ['name' => 'Juan Dela Cruz',                              'agreed_monthly_payment' => 2000.00, 'is_active' => true],
            ['name' => 'Maria Garcia',                                'agreed_monthly_payment' => 3000.00, 'is_active' => true],
            ['name' => 'Lorna Villanueva',                            'agreed_monthly_payment' => 1500.00, 'is_active' => false],

            // Company customers with credit
            ['name' => 'Engr. Rodel Pascual - Coca-Cola Beverages',   'agreed_monthly_payment' => 15000.00, 'is_active' => true],
            ['name' => 'Grace Lim - Pepsi-Cola Products',             'agreed_monthly_payment' => 12000.00, 'is_active' => true],
        ];

        foreach ($creditCustomers as $data) {
            $customer = Customer::where('name', $data['name'])->first();

            if ($customer) {
                CreditAccount::firstOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'agreed_monthly_payment' => $data['agreed_monthly_payment'],
                        'is_active'              => $data['is_active'],
                    ]
                );
            }
        }
    }
}
