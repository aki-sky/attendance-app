<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // 当月を含む過去6か月の開始日
        $startDate = now()->startOfMonth()->subMonths(5);
        $endDate = now()->endOfMonth();

        // ログインユーザーの過去6か月の勤怠を取得
        $attendances = Attendance::with('rests')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startDate, $endDate])
            ->orderBy('work_date')
            ->get();

        $monthlyTrend = [];
        $totalWorkMinutes = 0;
        $totalOvertimeMinutes = 0;
        $workDays = 0;

        // 月ごとの集計（データがない月も0で表示）
        for ($i = 0; $i < 6; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $monthKey = $month->format('Y-m');

            $monthlyTrend[$monthKey] = [
                'month' => $month->format('Y-m'),
                'work_minutes' => 0,
                'overtime_minutes' => 0,
            ];
        }

        foreach ($attendances as $attendance) {
            // 退勤済みの勤怠だけを集計
            if (!$attendance->clock_in || !$attendance->clock_out) {
                continue;
            }

            $breakSeconds = $attendance->rests->sum(function ($rest) {
                if (!$rest->rest_start || !$rest->rest_end) {
                    return 0;
                }

                return $rest->rest_start->diffInSeconds($rest->rest_end);
            });

            $clockIn = Carbon::parse($attendance->clock_in);
            $clockOut = Carbon::parse($attendance->clock_out);

            $workSeconds = max(
                0,
                $clockIn->diffInSeconds($clockOut) - $breakSeconds
            );

            // 分単位に変換
            $workMinutes = intdiv($workSeconds, 60);
            $overtimeMinutes = max(0, $workMinutes - 8 * 60);

            $monthKey = Carbon::parse($attendance->work_date)->format('Y-m');

            $monthlyTrend[$monthKey]['work_minutes'] += $workMinutes;
            $monthlyTrend[$monthKey]['overtime_minutes'] += $overtimeMinutes;

            $totalWorkMinutes += $workMinutes;
            $totalOvertimeMinutes += $overtimeMinutes;
            $workDays++;
        }

        // 今月の異常検知
        $thisMonthAttendances = $attendances->filter(function ($attendance) {
            return Carbon::parse($attendance->work_date)->isCurrentMonth();
        });

        $anomalies = [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ];

        foreach ($thisMonthAttendances as $attendance) {
            if (
                $attendance->clock_in
                && $attendance->clock_in->format('H:i') > '09:00'
            ) {
                $anomalies['late_count']++;
            }

            if (
                $attendance->clock_out
                && $attendance->clock_out->format('H:i') < '18:00'
            ) {
                $anomalies['early_leave_count']++;
            }

            if ($attendance->clock_in && $attendance->clock_out) {
                $breakSeconds = $attendance->rests->sum(function ($rest) {
                    if (!$rest->rest_start || !$rest->rest_end) {
                        return 0;
                    }

                    return $rest->rest_start->diffInSeconds($rest->rest_end);
                });

                $clockIn = Carbon::parse($attendance->clock_in);
                $clockOut = Carbon::parse($attendance->clock_out);

                $workSeconds = max(
                    0,
                    $clockIn->diffInSeconds($clockOut) - $breakSeconds
                );

                if ($workSeconds > 10 * 60 * 60) {
                    $anomalies['long_work_count']++;
                }
            }
        }

        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $workDays > 0
                ? intdiv($totalWorkMinutes, $workDays)
                : 0,
        ];

        return view('reports.index', [
            'summary' => $summary,
            'monthlyTrend' => array_values($monthlyTrend),
            'anomalies' => $anomalies,
        ]);
    }
}
