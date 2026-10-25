<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\RequestAttendance;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequestAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザー1を取得
        $user = User::where('email', 'user1@example.com')->firstOrFail();

        // ユーザー1の2026年9月1日の勤怠を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', '2026-09-01')
            ->firstOrFail();

        // 同じ勤怠に紐づく既存の修正申請を削除
        RequestAttendance::where('attendance_id', $attendance->id)->delete();

        // 申請中
        RequestAttendance::create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-09-01 09:30:00',
            'request_clock_out' => '2026-09-01 18:30:00',
            'request_note' => '遅延のため',
            'status' => 0,
            'approved_at' => null,
        ]);

        // 承認済み
        RequestAttendance::create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-09-01 09:30:00',
            'request_clock_out' => '2026-09-01 18:30:00',
            'request_note' => '遅延のため',
            'status' => 1,
            'approved_at' => '2026-09-01 10:00:00',
        ]);
    }
}
