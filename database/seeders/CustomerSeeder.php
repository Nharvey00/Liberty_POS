<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_PH');

        // Always keep a generic walk-in
        Customer::firstOrCreate(
            ['first_name' => '-- Walk-in', 'last_name' => 'Customer --'],
            ['customer_type' => 'Household']
        );

        $b2bTypes = ['Tertiary', 'MRO', 'Service Station', 'Commercial', 'Main Store'];

        // Generate 29 logic-based customers (making 30 total)
        for ($i = 0; $i < 29; $i++) {
            // We give Household a higher chance of being selected to balance the B2B accounts
            $type = $faker->randomElement(array_merge(['Household', 'Household', 'Household'], $b2bTypes));
            
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $businessName = null;

            // Generate realistic business names based on the classification
            if (in_array($type, $b2bTypes)) {
                if ($type === 'Tertiary') $businessName = $lastName . ' Eatery & Canteen';
                elseif ($type === 'Commercial') $businessName = $lastName . ' Commercial Enterprises';
                elseif ($type === 'Service Station') $businessName = $lastName . ' Gas & Service';
                elseif ($type === 'MRO') $businessName = $lastName . ' Industrial Maintenance';
                elseif ($type === 'Main Store') $businessName = 'Liberty LPG Main - ' . $faker->city();
            }

            Customer::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'business_name' => $businessName,
                'customer_type' => $type,
                'phone' => $faker->phoneNumber(),
            ]);
        }
    }
}