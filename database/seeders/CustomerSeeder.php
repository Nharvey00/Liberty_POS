<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        // NOTE: Live Supabase DB only has: id, name, customer_type, phone, address, timestamps
        // Columns business_name and tin_number do NOT exist in the live schema.
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

            // Company / Corporate customers (Coke-type residual calculation)
            [
                'name'          => 'Engr. Rodel Pascual - Coca-Cola Beverages',
                'customer_type' => 'Company',
                'phone'         => '09987654321',
                'address'       => 'NFA Compound, Panacan Industrial Area, Davao City',
            ],
            [
                'name'          => 'Grace Lim - Pepsi-Cola Products',
                'customer_type' => 'Company',
                'phone'         => '09175551234',
                'address'       => 'PHIVIDEC Industrial Estate, Tagoloan, Misamis Oriental',
            ],
            [
                'name'          => 'Dennis Ocampo - San Miguel Brewery',
                'customer_type' => 'Company',
                'phone'         => '09228889999',
                'address'       => 'Mandug Industrial Zone, Davao City',
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
