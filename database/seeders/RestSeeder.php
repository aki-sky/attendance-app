<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AttendanceSeeder::class,
            RestSeeder::class,
            RequestAttendanceSeeder::class,
            RequestRestSeeder::class,
        ]);
    }
}
