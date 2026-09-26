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
                'name'          => 'Juan Dela Cruz',
                'customer_type' => 'Normal',
                'phone'         => '09171234567',
                'address'       => 'Purok 7, Catalunan Grande, Davao City',
            ],
            [
                'name'          => 'Maria Garcia',
                'customer_type' => 'Normal',
                'phone'         => '09281234567',
                'address'       => 'Blk 3 Lot 5, Buhangin, Davao City',
            ],
            [
                'name'          => 'Pedro Bautista',
                'customer_type' => 'Normal',
                'phone'         => '09351234567',
                'address'       => 'Purok 2, Toril, Davao City',
            ],
            [
                'name'          => 'Lorna Villanueva',
                'customer_type' => 'Normal',
                'phone'         => '09191234567',
                'address'       => 'Matina Crossing, Davao City',
            ],
            [
                'name'          => 'Roberto Tan',
                'customer_type' => 'Normal',
                'phone'         => '09061234567',
                'address'       => 'Bangkal, Davao City',
            ],

            // Company customers (billed by actual KG consumed via residual weight)
            [
                'name'          => 'Engr. Rodel Pascual',
                'business_name' => 'Coca-Cola Beverages Philippines',
                'customer_type' => 'Company',
                'phone'         => '09987654321',
                'address'       => 'NFA Compound, Panacan Industrial Area, Davao City',
                'tin_number'    => '123-456-789-000',
            ],
            [
                'name'          => 'Grace Lim',
                'business_name' => 'Pepsi-Cola Products Philippines',
                'customer_type' => 'Company',
                'phone'         => '09175551234',
                'address'       => 'PHIVIDEC Industrial Estate, Tagoloan, Misamis Oriental',
                'tin_number'    => '234-567-890-000',
            ],
            [
                'name'          => 'Dennis Ocampo',
                'business_name' => 'San Miguel Brewery Inc.',
                'customer_type' => 'Company',
                'phone'         => '09228889999',
                'address'       => 'Mandug Industrial Zone, Davao City',
                'tin_number'    => '345-678-901-000',
            ],
        ];

        foreach ($customers as $data) {
            Customer::firstOrCreate(
                ['name' => $data['name'], 'customer_type' => $data['customer_type']],
                $data
            );
        }
    }
}
