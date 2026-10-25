<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\RequestAttendance;
use App\Models\RequestRest;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequestRestSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザー1を取得
        $user = User::where('email', 'user1@example.com')->firstOrFail();

        // ユーザー1の2026年9月1日の勤怠を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', '2026-09-01')
            ->firstOrFail();

        // この勤怠に紐づく修正申請を取得
        $requestAttendances = RequestAttendance::where(
            'attendance_id',
            $attendance->id
        )->get();

        foreach ($requestAttendances as $requestAttendance) {
            // 既存の申請休憩を削除
            RequestRest::where(
                'request_attendance_id',
                $requestAttendance->id
            )->delete();

            // 休憩申請を登録
            RequestRest::create([
                'request_attendance_id' => $requestAttendance->id,
                'request_rest_start' => '2026-09-01 12:00:00',
                'request_rest_end' => '2026-09-01 13:00:00',
            ]);
        }
    }
}
