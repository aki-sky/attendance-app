<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAttendanceRequest;
use App\Http\Requests\RequestRestRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class RequestAttendanceController extends Controller
{
    /**
     * 勤怠修正申請を登録
     */
    public function store(
        RequestAttendanceRequest $request,
        RequestRestRequest $restRequest,
        Attendance $attendance
    ) {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 管理者は対象の勤怠を直接修正できる
        if ($user->role === 1) {
            $attendance = Attendance::with('rests')
                ->findOrFail($attendance->id);
        } else {
            // 一般ユーザーは自分の勤怠のみ修正申請できる
            $attendance = $user->attendances()
                ->with('rests')
                ->findOrFail($attendance->id);
        }

        // 休憩データを取得
        $breakIns = $restRequest->input('new_break_in', []);
        $breakOuts = $restRequest->input('new_break_out', []);

        $breakCount = max(
            count($breakIns),
            count($breakOuts)
        );

        DB::transaction(function () use (
            $user,
            $attendance,
            $request,
            $breakIns,
            $breakOuts,
            $breakCount
        ) {
            $workDate = $attendance->work_date->format('Y-m-d');

            // 管理者の場合は申請せず、直接反映
            if ($user->role === 1) {
                $attendance->update([
                    'clock_in' => $workDate . ' ' . $request->new_clock_in,
                    'clock_out' => $request->new_clock_out
                        ? $workDate . ' ' . $request->new_clock_out
                        : null,
                    'note' => $request->comment,
                ]);

                // 既存の休憩を削除して、入力された休憩を保存
                $attendance->rests()->delete();

                for ($i = 0; $i < $breakCount; $i++) {
                    $breakIn = $breakIns[$i] ?? null;
                    $breakOut = $breakOuts[$i] ?? null;

                    // 開始・終了が両方空欄なら保存しない
                    if (blank($breakIn) && blank($breakOut)) {
                        continue;
                    }

                    // 片方だけ入力されている場合は保存しない
                    if (blank($breakIn) || blank($breakOut)) {
                        continue;
                    }

                    $attendance->rests()->create([
                        'rest_start' => $workDate . ' ' . $breakIn,
                        'rest_end' => $workDate . ' ' . $breakOut,
                    ]);
                }

                return;
            }

            // 一般ユーザーは従来どおり修正申請を作成
            $requestAttendance = $attendance->Requestattendances()->create([
                'request_clock_in' => $workDate . ' ' . $request->new_clock_in,

                'request_clock_out' => $request->new_clock_out
                    ? $workDate . ' ' . $request->new_clock_out
                    : null,

                'request_note' => $request->comment,
                'status' => 0,
            ]);

            for ($i = 0; $i < $breakCount; $i++) {
                $breakIn = $breakIns[$i] ?? null;
                $breakOut = $breakOuts[$i] ?? null;

                if (blank($breakIn) && blank($breakOut)) {
                    continue;
                }

                if (blank($breakIn) || blank($breakOut)) {
                    continue;
                }

                $requestAttendance->restRequests()->create([
                    'request_rest_start' => $workDate . ' ' . $breakIn,
                    'request_rest_end' => $workDate . ' ' . $breakOut,
                ]);
            }
        });

        // 管理者と一般ユーザーでメッセージを分ける
        if ($user->role === 1) {
            return redirect()
                ->route('attendance.show', $attendance)
                ->with('success', '勤怠を修正しました。');
        }

        return redirect()
            ->route('attendance.show', $attendance)
            ->with('success', '勤怠修正申請を送信しました。');
    }

    /**
     * 自分の勤怠修正申請一覧
     */
    public function index()
    {
        $user = auth()->user();

        // ログインユーザーが取得できない場合
        if (!$user) {
            abort(401);
        }

        // 管理者の場合
        if ($user->role === 1) {

            $applications = \App\Models\RequestAttendance::with([
                'attendance.user',
            ])
                ->latest()
                ->get()
                ->map(function ($requestAttendance) {

                    $requestAttendance->approval_status =
                        $requestAttendance->status === 0
                        ? '承認待ち'
                        : '承認済み';

                    $requestAttendance->comment =
                        $requestAttendance->request_note;

                    $requestAttendance->application_date =
                        $requestAttendance->created_at;

                    $requestAttendance->setRelation(
                        'user',
                        $requestAttendance->attendance->user
                    );

                    $requestAttendance->setRelation(
                        'AttendanceRecord',
                        $requestAttendance->attendance
                    );

                    return $requestAttendance;
                });

            return view('admin.admin-application-list', [
                'applications' => $applications,
            ]);
        }

        // 一般ユーザーの場合
        $applications = \App\Models\RequestAttendance::whereHas(
            'attendance',
            function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        )
            ->with('attendance')
            ->latest()
            ->get();

        $formattedApplications = $applications->map(function ($application) {

            return [
                'id' => $application->id,
                'approval_status' => $application->status === 0
                    ? '承認待ち'
                    : '承認済み',
                'date' => $application->attendance->work_date
                    ->format('Y/m/d'),
                'comment' => $application->request_note,
                'application_date' => $application->created_at
                    ->format('Y/m/d H:i'),
            ];
        });

        return view('user.user-application-list', [
            'user' => $user,
            'formattedApplications' => $formattedApplications,
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();

        $application = \App\Models\RequestAttendance::whereHas(
            'attendance',
            function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        )
            ->with([
                'attendance',
                'restRequests',
            ])
            ->findOrFail($id);

        $attendance = $application->attendance;

        $data = [
            'id' => $attendance->id,
            'application' => $application,
            'year' => $attendance->work_date->format('Y年'),
            'date' => $attendance->work_date->format('n月j日'),

            'clock_in' => $application->request_clock_in
                ? \Carbon\Carbon::parse($application->request_clock_in)->format('H:i')
                : null,

            'clock_out' => $application->request_clock_out
                ? \Carbon\Carbon::parse($application->request_clock_out)->format('H:i')
                : null,

            'breaks' => $application->restRequests->map(function ($rest) {
                return [
                    'break_in' => $rest->request_rest_start
                        ? \Carbon\Carbon::parse($rest->request_rest_start)->format('H:i')
                        : null,

                    'break_out' => $rest->request_rest_end
                        ? \Carbon\Carbon::parse($rest->request_rest_end)->format('H:i')
                        : null,
                ];
            }),

            'comment' => $application->request_note,
        ];

        return view('user.user-detail', [
            'user' => $user,
            'data' => $data,
        ]);
    }
}
