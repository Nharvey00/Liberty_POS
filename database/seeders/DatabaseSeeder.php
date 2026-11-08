<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run with: php artisan db:seed
     * Fresh reset: php artisan migrate:fresh --seed
     *
     * Order matters — each seeder depends on the ones above it.
     * All seeders use firstOrCreate / existence checks so they
     * are safe to re-run without duplicating data.
     */
    public function run(): void
    {
        $this->call([
            // 1. Foundation: Roles must exist before Users
            RoleSeeder::class,

            // 2. Users: need Roles
            UserSeeder::class,
        ]);

        // 3. Comprehensive LPG Operational Demo Data (15 Products, 10 Customers,
        //    Orders, Stock Ins/Outs + Voids, Credit Accounts, and Huram Tank Loans)
        //    CRITICAL PRODUCTION GUARD: Only seed fake demo data in local or testing!
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                ComprehensiveLpgDemoSeeder::class,
            ]);
        }
    }
}
