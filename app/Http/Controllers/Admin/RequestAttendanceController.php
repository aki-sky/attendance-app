<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestAttendance;
use Illuminate\Support\Facades\DB;

class RequestAttendanceController extends Controller
{
    /**
     * 申請一覧
     */
    public function index()
    {
        $applications = RequestAttendance::with([
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

    public function show($id)
    {
        $application = RequestAttendance::with([
            'attendance.user',
            'restRequests',
        ])->findOrFail($id);

        $application->approval_status =
            $application->status === 0
            ? '承認待ち'
            : '承認済み';

        $application->comment = old(
            'comment',
            $application->request_note
        );

        $application->new_date =
            $application->attendance->work_date;

        $application->new_clock_in = old(
            'new_clock_in',
            $application->request_clock_in
                ? $application->request_clock_in->format('H:i')
                : null
        );

        $application->new_clock_out = old(
            'new_clock_out',
            $application->request_clock_out
                ? $application->request_clock_out->format('H:i')
                : null
        );

        $oldBreakIns = old('new_break_in', []);
        $oldBreakOuts = old('new_break_out', []);

        $application->proposalBreaks = $application->restRequests
            ->filter(function ($rest) {
                return $rest->request_rest_start !== null
                    || $rest->request_rest_end !== null;
            })
            ->values()
            ->map(function ($rest, $index) use ($oldBreakIns, $oldBreakOuts) {
                return (object) [
                    'break_in' => array_key_exists($index, $oldBreakIns)
                        ? $oldBreakIns[$index]
                        : $rest->request_rest_start?->format('H:i'),

                    'break_out' => array_key_exists($index, $oldBreakOuts)
                        ? $oldBreakOuts[$index]
                        : $rest->request_rest_end?->format('H:i'),
                ];
            });

        return view('admin.admin-application-detail', [
            'application' => $application,
            'user' => $application->attendance->user,
        ]);
    }

    public function approve($id)
    {
        $application = RequestAttendance::with([
            'attendance',
            'restRequests',
        ])->findOrFail($id);

        // すでに承認済みなら何もしない
        if ($application->status === 1) {
            return redirect()
                ->route('admin.application.show', $application->id);
        }

        $attendance = $application->attendance;

        DB::transaction(function () use ($application, $attendance) {

            // 勤怠本体を申請内容に更新
            $attendance->update([
                'clock_in' => $application->request_clock_in,
                'clock_out' => $application->request_clock_out,
                'note' => $application->request_note,
            ]);

            // 既存の休憩を削除
            $attendance->rests()->delete();

            // 申請された休憩を反映
            foreach ($application->restRequests as $requestRest) {
                $attendance->rests()->create([
                    'rest_start' => $requestRest->request_rest_start,
                    'rest_end' => $requestRest->request_rest_end,
                ]);
            }

            // 申請を承認済みにする
            $application->update([
                'status' => 1,
                'approved_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.application.show', $application->id);
    }
}
