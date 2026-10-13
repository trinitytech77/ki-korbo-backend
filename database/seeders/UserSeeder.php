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
        User::firstOrCreate(
            ['email' => 'admin@korbo.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role' => UserRole::ADMIN->value,
                'status' => 'ACTIVE',
            ]
        );
    }
}