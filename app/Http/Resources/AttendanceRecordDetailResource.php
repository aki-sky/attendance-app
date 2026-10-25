<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AttendanceRecordDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            'date' => $this->work_date?->format('Y-m-d'),

            'clock_in' => $this->clock_in
                ? Carbon::parse($this->clock_in)->format('H:i:s')
                : null,

            'clock_out' => $this->clock_out
                ? Carbon::parse($this->clock_out)->format('H:i:s')
                : null,

            'breaks' => $this->rests->map(function ($rest) {
                return [
                    'id' => $rest->id,
                    'break_in' => $rest->rest_start
                        ? Carbon::parse($rest->rest_start)->format('H:i:s')
                        : null,
                    'break_out' => $rest->rest_end
                        ? Carbon::parse($rest->rest_end)->format('H:i:s')
                        : null,
                ];
            })->values(),

            'applications' => $this->Requestattendances->map(function ($application) {
                return [
                    'id' => $application->id,
                    'request_clock_in' => $application->request_clock_in,
                    'request_clock_out' => $application->request_clock_out,
                    'request_note' => $application->request_note,
                    'status' => $application->status,
                    'approved_at' => $application->approved_at,
                ];
            })->values(),

            'comment' => $this->note,
        ];
    }
}
