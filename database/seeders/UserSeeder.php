<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin account
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'), // change later
                'role' => 'admin',
                'status' => "active"
            ]
        );

        // Teacher account
        User::updateOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Angela Smith',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'status' => "active"
            ]
        );

        // Operator account
        User::updateOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => 'Operator User',
                'password' => Hash::make('password123'),
                'role' => 'operator',
                'status' => "active"
            ]
        );
    }
}
