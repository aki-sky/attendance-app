<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    public function index(Request $request, User $user)
    {
        // 表示する月を取得
        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))->startOfMonth()
            : now()->startOfMonth();

        // 前月・翌月
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 指定ユーザーの指定月の勤怠を取得
        $attendances = $user->attendances()
            ->with('rests')
            ->whereBetween('work_date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ])
            ->orderBy('work_date')
            ->get();

        // Bladeで使用する形式に変換
        $formattedAttendanceRecords = $attendances->map(function ($attendance) {

            $totalBreakSeconds = $attendance->rests->sum(function ($rest) {
                if (!$rest->rest_start || !$rest->rest_end) {
                    return 0;
                }

                return $rest->rest_start->diffInSeconds($rest->rest_end);
            });

            $totalTimeSeconds = 0;

            if ($attendance->clock_in && $attendance->clock_out) {
                $totalTimeSeconds = $attendance->clock_in
                    ->diffInSeconds($attendance->clock_out)
                    - $totalBreakSeconds;
            }

            return [
                'id' => $attendance->id,
                'date' => $attendance->work_date->isoFormat('MM/DD（ddd）'),
                'clock_in' => $attendance->clock_in?->format('H:i'),
                'clock_out' => $attendance->clock_out?->format('H:i'),
                'total_break_time' => $totalBreakSeconds,
                'total_time' => $totalTimeSeconds,
            ];
        });

        return view('admin.staff-attendance-list', [
            'user' => $user,
            'date' => $date,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'formattedAttendanceRecords' => $formattedAttendanceRecords,
        ]);
    }

    public function export(Request $request)
    {
        // フォームからユーザーIDと対象年月を取得
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $date = \Carbon\Carbon::createFromFormat(
            'Y-m',
            $validated['year_month']
        )->startOfMonth();

        // 指定ユーザーの指定月の勤怠を取得
        $attendances = $user->attendances()
            ->with('rests')
            ->whereBetween('work_date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ])
            ->orderBy('work_date')
            ->get();

        // CSVの行を作成
        $rows = [];
        $rows[] = ['日付', '出勤', '退勤', '休憩時間', '合計勤務時間'];

        $formatDuration = function ($seconds) {
            $seconds = max(0, $seconds);

            return sprintf(
                '%02d:%02d',
                intdiv($seconds, 3600),
                intdiv($seconds % 3600, 60)
            );
        };

        foreach ($attendances as $attendance) {
            $totalBreakSeconds = $attendance->rests->sum(function ($rest) {
                if (!$rest->rest_start || !$rest->rest_end) {
                    return 0;
                }

                return $rest->rest_start->diffInSeconds($rest->rest_end);
            });

            $totalWorkSeconds = 0;

            if ($attendance->clock_in && $attendance->clock_out) {
                $totalWorkSeconds = $attendance->clock_in
                    ->diffInSeconds($attendance->clock_out)
                    - $totalBreakSeconds;
            }

            $rows[] = [
                $attendance->work_date->format('Y/m/d'),
                $attendance->clock_in?->format('H:i') ?? '',
                $attendance->clock_out?->format('H:i') ?? '',
                $formatDuration($totalBreakSeconds),
                $formatDuration($totalWorkSeconds),
            ];
        }

        // CSVをメモリ上で作成
        $stream = fopen('php://temp', 'r+');

        // Excelでの文字化け対策（UTF-8 BOM）
        fwrite($stream, "\xEF\xBB\xBF");

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $filename = sprintf(
            '勤怠一覧_%s_%s.csv',
            $user->name,
            $date->format('Y-m')
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . rawurlencode($filename) . '"',
        ]);
    }
}
