<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run with: php artisan db:seed
     *
     * Order matters — each seeder depends on the ones above it.
     * All seeders use firstOrCreate / existence checks so they
     * are safe to run multiple times without duplicating data.
     */
    public function run(): void
    {
        $this->call([
            // 1. Foundation: Roles must exist before Users
            RoleSeeder::class,

            // 2. Users: need Roles
            UserSeeder::class,

            // 3. Customers: standalone, no FK dependencies
            CustomerSeeder::class,

            // 4. Products: standalone, no FK dependencies
            ProductSeeder::class,

            // 5. Credit Accounts: need Customers
            CreditAccountSeeder::class,

            // 6. Orders + Order Items + Credit Ledger Charges:
            //    need Customers, Users, Products, CreditAccounts
            OrderSeeder::class,

            // 7. Stock Ins: need Products
            StockInSeeder::class,

            // 8. Stock Outs: need Products
            StockOutSeeder::class,

            // 9. Payments + Credit Ledger Payment entries:
            //    need CreditAccounts (must run AFTER OrderSeeder)
            PaymentSeeder::class,

            // 10. Statements of Account: need CreditAccounts + Ledger data
            //     (must run LAST — calculates total_due from ledger)
            StatementOfAccountSeeder::class,
        ]);
    }
}