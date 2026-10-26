<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@korbo.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => UserRole::ADMIN->value,
                'status' => 'ACTIVE',
            ]
        );

        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => UserRole::ADMIN->value,
            'guard_name' => 'web',
        ]);

        if (!$user->hasRole(UserRole::ADMIN->value)) {
            $user->assignRole($role);
        }
    }
}