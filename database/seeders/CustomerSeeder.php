<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            // Normal walk-in / delivery customers
            [
                'first_name'    => 'Juan',
                'middle_name'   => 'Dela',
                'last_name'     => 'Cruz',
                'suffix'        => null,
                'customer_type' => 'Normal',
                'phone'         => '09171234567',
                'address'       => 'Purok 7, Catalunan Grande, Davao City',
            ],
            [
                'first_name'    => 'Maria',
                'middle_name'   => null,
                'last_name'     => 'Garcia',
                'suffix'        => null,
                'customer_type' => 'Normal',
                'phone'         => '09281234567',
                'address'       => 'Blk 3 Lot 5, Buhangin, Davao City',
            ],
            [
                'first_name'    => 'Pedro',
                'middle_name'   => null,
                'last_name'     => 'Bautista',
                'suffix'        => null,
                'customer_type' => 'Normal',
                'phone'         => '09351234567',
                'address'       => 'Purok 2, Toril, Davao City',
            ],
            [
                'first_name'    => 'Lorna',
                'middle_name'   => null,
                'last_name'     => 'Villanueva',
                'suffix'        => null,
                'customer_type' => 'Normal',
                'phone'         => '09191234567',
                'address'       => 'Matina Crossing, Davao City',
            ],
            [
                'first_name'    => 'Roberto',
                'middle_name'   => null,
                'last_name'     => 'Tan',
                'suffix'        => null,
                'customer_type' => 'Normal',
                'phone'         => '09061234567',
                'address'       => 'Bangkal, Davao City',
            ],

            // Company / Coke customers (billed by actual KG consumed via residual weight)
            [
                'first_name'    => 'Rodel',
                'middle_name'   => null,
                'last_name'     => 'Pascual',
                'suffix'        => null,
                'business_name' => 'Coca-Cola Beverages Philippines',
                'customer_type' => 'Coke (Residual)',
                'phone'         => '09987654321',
                'address'       => 'NFA Compound, Panacan Industrial Area, Davao City',
                'tin_number'    => '123-456-789-000',
            ],
            [
                'first_name'    => 'Grace',
                'middle_name'   => null,
                'last_name'     => 'Lim',
                'suffix'        => null,
                'business_name' => 'Pepsi-Cola Products Philippines',
                'customer_type' => 'Company',
                'phone'         => '09175551234',
                'address'       => 'PHIVIDEC Industrial Estate, Tagoloan, Misamis Oriental',
                'tin_number'    => '234-567-890-000',
            ],
            [
                'first_name'    => 'Dennis',
                'middle_name'   => null,
                'last_name'     => 'Ocampo',
                'suffix'        => null,
                'business_name' => 'San Miguel Brewery Inc.',
                'customer_type' => 'Company',
                'phone'         => '09228889999',
                'address'       => 'Mandug Industrial Zone, Davao City',
                'tin_number'    => '345-678-901-000',
            ],
        ];

        foreach ($customers as $data) {
            Customer::firstOrCreate(
                [
                    'first_name'    => $data['first_name'],
                    'last_name'     => $data['last_name'],
                    'customer_type' => $data['customer_type'],
                ],
                $data
            );
        }
    }
}
