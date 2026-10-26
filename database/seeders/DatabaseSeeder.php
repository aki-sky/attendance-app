<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ユーザーを作成・更新
        $this->call([
            UserSeeder::class,
        ]);

        // 3. 新しい勤怠・休憩・申請データを作成
        $this->call([
            AttendanceSeeder::class,
            RestSeeder::class,
            RequestAttendanceSeeder::class,
            RequestRestSeeder::class,
        ]);
    }
}
