<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 表示する日付を取得
        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))
            : now();

        // 前日・翌日
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 全ユーザーを取得
        $users = User::orderBy('id')->get();

        // 指定日の勤怠を取得
        $attendanceRecords = Attendance::with('rests')
            ->whereDate('work_date', $date)
            ->get()
            ->map(function ($attendance) {
                $clockIn = $attendance->clock_in;
                $clockOut = $attendance->clock_out;

                // 休憩時間の合計（秒）
                $totalBreakSeconds = $attendance->rests->sum(
                    function ($rest) {
                        if (!$rest->rest_start || !$rest->rest_end) {
                            return 0;
                        }

                        $start = \Carbon\Carbon::parse($rest->rest_start);
                        $end = \Carbon\Carbon::parse($rest->rest_end);

                        return max(0, $start->diffInSeconds($end));
                    }
                );

                // 休憩時間を H:i 形式に変換
                $attendance->total_break_time = $totalBreakSeconds > 0
                    ? sprintf(
                        '%02d:%02d',
                        intdiv($totalBreakSeconds, 3600),
                        intdiv($totalBreakSeconds % 3600, 60)
                    )
                    : null;

                // 勤務時間の合計（秒）
                $totalSeconds = 0;

                if ($clockIn && $clockOut) {
                    $totalSeconds = max(
                        0,
                        $clockIn->diffInSeconds($clockOut)
                            - $totalBreakSeconds
                    );
                }

                // 勤務時間を H:i 形式に変換
                $attendance->total_time = $totalSeconds > 0
                    ? sprintf(
                        '%02d:%02d',
                        intdiv($totalSeconds, 3600),
                        intdiv($totalSeconds % 3600, 60)
                    )
                    : null;

                // 出勤・退勤時刻を表示用に変換
                $attendance->clock_in = $clockIn?->format('H:i');
                $attendance->clock_out = $clockOut?->format('H:i');

                // 休憩の開始・終了時刻を表示用に変換
                $attendance->rests->each(function ($rest) {
                    $rest->rest_start = $rest->rest_start?->format('H:i');
                    $rest->rest_end = $rest->rest_end?->format('H:i');
                });

                return $attendance;
            });


        return view('admin.admin-attendance-list', [
            'date' => $date,
            'previousDay' => $previousDay,
            'nextDay' => $nextDay,
            'users' => $users,
            'attendanceRecords' => $attendanceRecords,
        ]);
    }
}
