<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RestSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザー1を取得
        $user = User::where('email', 'user1@example.com')->firstOrFail();

        // ユーザー1の勤怠を取得
        $attendances = Attendance::where('user_id', $user->id)->get();

        foreach ($attendances as $attendance) {
            $day = Carbon::parse($attendance->work_date);

            // 休憩を登録
            Rest::create([
                'attendance_id' => $attendance->id,
                'rest_start' => $day->copy()->setTime(12, 0),
                'rest_end'   => $day->copy()->setTime(13, 0),
            ]);
        }
    }
}
