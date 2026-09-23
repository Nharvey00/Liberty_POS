<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = Role::where('role_name', 'Level 3')->first();
        $manager = Role::where('role_name', 'Level 2')->first();
        $cashier = Role::where('role_name', 'Level 1')->first();

        // Owner / Admin
        User::firstOrCreate(
            ['email' => 'admin@libertylpg.com'],
            [
                'name' => 'Admin Owner',
                'password' => Hash::make('password'),
                'role_id' => $owner->id,
            ]
        );

        // Manager
        User::firstOrCreate(
            ['email' => 'manager@libertylpg.com'],
            [
                'name' => 'Mark Reyes',
                'password' => Hash::make('password'),
                'role_id' => $manager->id,
            ]
        );

        // Cashier 1
        User::firstOrCreate(
            ['email' => 'cashier1@libertylpg.com'],
            [
                'name' => 'Ana Santos',
                'password' => Hash::make('password'),
                'role_id' => $cashier->id,
            ]
        );

        // Cashier 2
        User::firstOrCreate(
            ['email' => 'cashier2@libertylpg.com'],
            [
                'name' => 'Rico Mendoza',
                'password' => Hash::make('password'),
                'role_id' => $cashier->id,
            ]
        );
    }
}
