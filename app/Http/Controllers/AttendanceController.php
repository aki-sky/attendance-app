<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面
     */
    public function create()
    {
        /** @var User $user */
        $user = auth()->user();

        $attendance = $user->attendances()
            ->whereDate('work_date', today())
            ->with('rests')
            ->first();

        // 勤務状態を取得
        if (!$attendance) {
            $user->attendance_status = '勤務外';
        } elseif ($attendance->clock_out) {
            $user->attendance_status = '退勤済';
        } elseif ($attendance->rests()->whereNull('rest_end')->exists()) {
            $user->attendance_status = '休憩中';
        } else {
            $user->attendance_status = '出勤中';
        }

        return view('user.attendance-register', [
            'user' => $user,
            'formattedDate' => today()->format('Y年m月d日'),
            'formattedTime' => now()->format('H:i'),
        ]);
    }

    /**
     * 出勤・退勤・休憩入・休憩戻
     */
    public function clock(AttendanceRequest $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $action = $request->input('action');

        $attendance = $user->attendances()
            ->whereDate('work_date', today())
            ->with('rests')
            ->first();

        // 出勤
        if ($action === 'clock_in') {

            if ($attendance) {
                return redirect()->route('attendance.create');
            }

            Attendance::create([
                'user_id' => $user->id,
                'work_date' => today(),
                'clock_in' => now(),
            ]);

            return redirect()->route('attendance.create');
        }

        // 出勤記録がない場合は、退勤・休憩操作をできない
        if (!$attendance) {
            return redirect()->route('attendance.create');
        }

        // 退勤
        if ($action === 'clock_out') {

            if ($attendance->clock_out) {
                return redirect()->route('attendance.create');
            }

            // 休憩中なら退勤させない
            $resting = $attendance->rests()
                ->whereNull('rest_end')
                ->exists();

            if ($resting) {
                return redirect()->route('attendance.create');
            }

            $attendance->update([
                'clock_out' => now(),
            ]);

            return redirect()->route('attendance.create');
        }

        // 休憩開始
        if ($action === 'break_in') {

            if ($attendance->clock_out) {
                return redirect()->route('attendance.create');
            }

            $resting = $attendance->rests()
                ->whereNull('rest_end')
                ->exists();

            if ($resting) {
                return redirect()->route('attendance.create');
            }

            $attendance->rests()->create([
                'rest_start' => now(),
            ]);

            return redirect()->route('attendance.create');
        }

        // 休憩終了
        if ($action === 'break_out') {

            if ($attendance->clock_out) {
                return redirect()->route('attendance.create');
            }

            $rest = $attendance->rests()
                ->whereNull('rest_end')
                ->latest('rest_start')
                ->first();

            if (!$rest) {
                return redirect()->route('attendance.create');
            }

            $rest->update([
                'rest_end' => now(),
            ]);

            return redirect()->route('attendance.create');
        }

        return redirect()->route('attendance.create');
    }

    /**
     * 勤怠一覧
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = auth()->user();

        $date = $request->query('date')
            ? \Carbon\Carbon::parse($request->query('date'))->startOfMonth()
            : now()->startOfMonth();

        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $attendances = $user->attendances()
            ->with('rests')
            ->whereBetween('work_date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ])
            ->orderBy('work_date')
            ->get();

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
                'date' => $attendance->work_date->format('m/d') . '（' . $attendance->work_date->isoFormat('ddd') . '）',
                'clock_in' => $attendance->clock_in?->format('H:i'),
                'clock_out' => $attendance->clock_out?->format('H:i'),
                'total_break_time' => $totalBreakSeconds,
                'total_time' => $totalTimeSeconds,
            ];
        });

        return view('user.user-attendance-list', [
            'date' => $date,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'formattedAttendanceRecords' => $formattedAttendanceRecords,
        ]);
    }

    /**
     * 勤怠詳細
     */
    public function show(Attendance $attendance)
    {
        /** @var User $user */
        $user = auth()->user();

        // 管理者の場合
        if ($user->role === 1) {
            $attendance = Attendance::with(['rests', 'user'])
                ->findOrFail($attendance->id);

            $attendanceRecord = [
                'id' => $attendance->id,
                'year' => $attendance->work_date->format('Y年'),
                'date' => $attendance->work_date->format('n月j日'),
                'clock_in' => $attendance->clock_in?->format('H:i'),
                'clock_out' => $attendance->clock_out?->format('H:i'),
                'breaks' => $attendance->rests->map(function ($rest) {
                    return [
                        'break_in' => $rest->rest_start?->format('H:i'),
                        'break_out' => $rest->rest_end?->format('H:i'),
                    ];
                })->toArray(),
                'comment' => $attendance->note,
            ];

            // バリデーションエラー時の入力値を復元
            $oldInput = session()->get('_old_input', []);

            if (!empty($oldInput)) {
                if (array_key_exists('new_clock_in', $oldInput)) {
                    $attendanceRecord['clock_in'] = $oldInput['new_clock_in'];
                }

                if (array_key_exists('new_clock_out', $oldInput)) {
                    $attendanceRecord['clock_out'] = $oldInput['new_clock_out'];
                }

                if (array_key_exists('comment', $oldInput)) {
                    $attendanceRecord['comment'] = $oldInput['comment'];
                }

                $oldBreakIns = $oldInput['new_break_in'] ?? [];
                $oldBreakOuts = $oldInput['new_break_out'] ?? [];

                if (
                    array_key_exists('new_break_in', $oldInput)
                    || array_key_exists('new_break_out', $oldInput)
                ) {
                    $breakCount = max(
                        count($oldBreakIns),
                        count($oldBreakOuts)
                    );

                    $breaks = [];

                    for ($i = 0; $i < $breakCount; $i++) {
                        $breakIn = $oldBreakIns[$i] ?? '';
                        $breakOut = $oldBreakOuts[$i] ?? '';

                        // 最後の追加用空欄は除外
                        if (
                            $i === $breakCount - 1
                            && $breakIn === ''
                            && $breakOut === ''
                        ) {
                            continue;
                        }

                        $breaks[] = [
                            'break_in' => $breakIn,
                            'break_out' => $breakOut,
                        ];
                    }

                    $attendanceRecord['breaks'] = $breaks;
                }
            }

            return view('admin.admin-detail', [
                'user' => $attendance->user,
                'attendanceRecord' => $attendanceRecord,
            ]);
        }

        // 一般ユーザーの場合
        $attendance = $user->attendances()
            ->with([
                'rests',
                'Requestattendances',
            ])
            ->findOrFail($attendance->id);

        $application = $attendance->Requestattendances()
            ->where('status', 0)
            ->latest()
            ->first();

        $clockIn = $application?->request_clock_in
            ?? $attendance->clock_in;

        $clockOut = $application?->request_clock_out
            ?? $attendance->clock_out;

        if ($application) {
            $breaks = $application->restRequests->map(function ($rest) {
                return [
                    'break_in' => $rest->request_rest_start?->format('H:i'),
                    'break_out' => $rest->request_rest_end?->format('H:i'),
                ];
            });
        } else {
            $breaks = $attendance->rests->map(function ($rest) {
                return [
                    'break_in' => $rest->rest_start?->format('H:i'),
                    'break_out' => $rest->rest_end?->format('H:i'),
                ];
            });
        }

        $comment = $application?->request_note
            ?? $attendance->note;

        // バリデーションエラー時の入力値を復元
        $oldInput = session()->get('_old_input', []);

        if (!$application && !empty($oldInput)) {
            if (array_key_exists('new_clock_in', $oldInput)) {
                $clockIn = $oldInput['new_clock_in'];
            }

            if (array_key_exists('new_clock_out', $oldInput)) {
                $clockOut = $oldInput['new_clock_out'];
            }

            if (array_key_exists('comment', $oldInput)) {
                $comment = $oldInput['comment'];
            }

            $oldBreakIns = $oldInput['new_break_in'] ?? [];
            $oldBreakOuts = $oldInput['new_break_out'] ?? [];

            $breakCount = max(
                count($oldBreakIns),
                count($oldBreakOuts)
            );

            $breaks = [];

            for ($i = 0; $i < $breakCount; $i++) {
                $breakIn = $oldBreakIns[$i] ?? '';
                $breakOut = $oldBreakOuts[$i] ?? '';

                // 最後の追加用空欄は $breaks に含めない
                if (
                    $i === $breakCount - 1
                    && $breakIn === ''
                    && $breakOut === ''
                ) {
                    continue;
                }

                $breaks[] = [
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ];
            }
        }

        // Carbonなどの日付オブジェクトと文字列の両方に対応
        $formatTime = function ($time) {
            if (empty($time)) {
                return '';
            }

            return $time instanceof \DateTimeInterface
                ? $time->format('H:i')
                : $time;
        };

        $data = [
            'id' => $attendance->id,
            'application' => $application,
            'year' => $attendance->work_date->format('Y年'),
            'date' => $attendance->work_date->format('n月j日'),
            'clock_in' => $formatTime($clockIn),
            'clock_out' => $formatTime($clockOut),
            'breaks' => $breaks,
            'comment' => $comment,
        ];

        return view('user.user-detail', [
            'user' => $user,
            'data' => $data,
        ]);
    }
}
