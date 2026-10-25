<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $clockIn = $this->clock_in;
        $clockOut = $this->clock_out;

        // 休憩時間の合計（分）
        $totalBreakMinutes = $this->rests->sum(function ($rest) {
            if (! $rest->rest_start || ! $rest->rest_end) {
                return 0;
            }

            return Carbon::parse($rest->rest_start)
                ->diffInMinutes(Carbon::parse($rest->rest_end));
        });

        // 労働時間（分）＝出勤から退勤まで − 休憩時間
        $totalWorkMinutes = null;

        if ($clockIn && $clockOut) {
            $totalMinutes = Carbon::parse($clockIn)
                ->diffInMinutes(Carbon::parse($clockOut));

            $totalWorkMinutes = max(0, $totalMinutes - $totalBreakMinutes);
        }

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'date' => $this->work_date?->format('Y-m-d'),
            'clock_in' => $clockIn
                ? Carbon::parse($clockIn)->format('H:i:s')
                : null,
            'clock_out' => $clockOut
                ? Carbon::parse($clockOut)->format('H:i:s')
                : null,
            'total_time' => $totalWorkMinutes !== null
                ? sprintf(
                    '%02d:%02d',
                    intdiv($totalWorkMinutes, 60),
                    $totalWorkMinutes % 60
                )
                : null,
            'total_break_time' => sprintf(
                '%02d:%02d',
                intdiv($totalBreakMinutes, 60),
                $totalBreakMinutes % 60
            ),
            'comment' => $this->note,
        ];
    }
}
