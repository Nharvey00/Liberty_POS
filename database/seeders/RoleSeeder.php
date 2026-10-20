<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            1 => 'Level 1',
            2 => 'Level 2',
            3 => 'Level 3',
        ];

        foreach ($roles as $id => $roleName) {
            $existingByName = Role::where('role_name', $roleName)->first();
            if ($existingByName && (int) $existingByName->id !== $id) {
                $targetExists = Role::where('id', $id)->exists();
                if (!$targetExists) {
                    DB::table('roles')->insert([
                        'id' => $id,
                        'role_name' => $roleName . '_tmp_' . $id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                User::withTrashed()->where('role_id', $existingByName->id)->update(['role_id' => $id]);
                DB::table('roles')->where('id', $existingByName->id)->delete();
                DB::table('roles')->where('id', $id)->update(['role_name' => $roleName]);
            } else {
                Role::firstOrCreate(
                    ['id' => $id],
                    ['id' => $id, 'role_name' => $roleName]
                );
            }
        }
    }
}
