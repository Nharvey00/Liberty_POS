<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['Level 1', 'Level 2', 'Level 3'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['role_name' => $roleName]);
        }
    }
}
