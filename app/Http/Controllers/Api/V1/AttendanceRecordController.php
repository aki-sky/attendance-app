<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use App\Http\Resources\AttendanceRecordDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧取得
     */
    public function index(
        IndexAttendanceRecordRequest $request
    ): AnonymousResourceCollection {
        $query = Attendance::with('user', 'rests', 'Requestattendances');

        $query->when(
            $request->filled('user_id'),
            fn($q) => $q->where('user_id', $request->input('user_id'))
        );

        $query->when(
            $request->filled('date'),
            fn($q) => $q->whereDate('work_date', $request->input('date'))
        );

        $query->when(
            $request->filled('month'),
            fn($q) => $q->whereBetween('work_date', [
                $request->input('month') . '-01',
                \Carbon\Carbon::parse($request->input('month') . '-01')
                    ->endOfMonth()
                    ->toDateString(),
            ])
        );

        $perPage = min((int) $request->input('per_page', 20), 100);

        return AttendanceRecordResource::collection(
            $query->orderByDesc('work_date')->paginate($perPage)
        );
    }

    /**
     * 勤怠詳細取得
     */
    public function show(Attendance $attendanceRecord): AttendanceRecordDetailResource
    {
        $attendanceRecord->load([
            'user',
            'rests',
            'Requestattendances',
        ]);

        return new AttendanceRecordDetailResource($attendanceRecord);
    }

    /**
     * 勤怠登録
     */
    public function store(
        StoreAttendanceRecordRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $attendance = Attendance::create([
            'user_id' => $data['user_id'],
            'work_date' => $data['date'],
            'clock_in' => $data['date'] . ' ' . $data['clock_in'],
            'clock_out' => isset($data['clock_out'])
                ? $data['date'] . ' ' . $data['clock_out']
                : null,
            'note' => $data['comment'] ?? null,
        ]);

        $attendance->load('user', 'rests', 'Requestattendances');

        return (new AttendanceRecordResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠更新
     */
    public function update(
        UpdateAttendanceRecordRequest $request,
        Attendance $attendanceRecord
    ): AttendanceRecordResource|JsonResponse {
        if (Gate::denies('update', $attendanceRecord)) {
            return response()->json([
                'error' => 'この操作を実行する権限がありません。',
            ], 403);
        }

        $data = $request->validated();

        // 新しい勤怠日。日付が省略された場合は既存の日付を使用
        $date = array_key_exists('date', $data)
            ? $data['date']
            : $attendanceRecord->work_date->format('Y-m-d');

        if (array_key_exists('date', $data)) {
            $attendanceRecord->work_date = $date;
        }

        // 時刻が省略された場合は既存の時刻を維持
        $clockIn = array_key_exists('clock_in', $data)
            ? $data['clock_in']
            : $attendanceRecord->clock_in?->format('H:i:s');

        $clockOut = array_key_exists('clock_out', $data)
            ? $data['clock_out']
            : $attendanceRecord->clock_out?->format('H:i:s');

        // 勤怠日が変更された場合、時刻の日付部分も合わせる
        if ($clockIn !== null) {
            $attendanceRecord->clock_in = $date . ' ' . $clockIn;
        }

        if ($clockOut !== null) {
            $attendanceRecord->clock_out = $date . ' ' . $clockOut;
        } elseif (array_key_exists('clock_out', $data)) {
            $attendanceRecord->clock_out = null;
        }

        if (array_key_exists('comment', $data)) {
            $attendanceRecord->note = $data['comment'];
        }

        $attendanceRecord->save();
        $attendanceRecord->load('user', 'rests', 'Requestattendances');

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠削除
     */
    public function destroy(Attendance $attendanceRecord): JsonResponse
    {
        if (Gate::denies('delete', $attendanceRecord)) {
            return response()->json([
                'error' => 'この操作を実行する権限がありません。',
            ], 403);
        }

        $attendanceRecord->delete();

        return response()->json(null, 204);
    }
}
