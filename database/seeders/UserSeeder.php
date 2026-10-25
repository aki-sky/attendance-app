<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 一般ユーザー1
        User::updateOrCreate(
            ['email' => 'user1@example.com'],
            [
                'name' => '一般ユーザー1',
                'password' => Hash::make('password'),
                'role' => 0,
                'email_verified_at' => now(),
            ]
        );

        // 一般ユーザー2
        User::updateOrCreate(
            ['email' => 'user2@example.com'],
            [
                'name' => '一般ユーザー2',
                'password' => Hash::make('password'),
                'role' => 0,
                'email_verified_at' => now(),
            ]
        );

        // 管理者
        User::updateOrCreate(
            ['email' => 'user3@example.com'],
            [
                'name' => '管理者',
                'password' => Hash::make('password'),
                'role' => 1,
                'email_verified_at' => now(),
            ]
        );
    }
}
