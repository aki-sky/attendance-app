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

        // 2. 既存の勤怠・申請データを整理
        $this->call([
            ClearAttendanceSeeder::class,
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
