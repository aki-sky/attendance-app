<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 更新権限はControllerでPolicyを使って判定する
        return true;
    }

    public function rules(): array
    {
        $attendanceRecord = $this->route('attendanceRecord');

        $attendanceId = is_object($attendanceRecord)
            ? $attendanceRecord->id
            : $attendanceRecord;

        $attendance = Attendance::find($attendanceId);

        $rules = [
            'date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
            ],
            'clock_in' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
            ],
            'clock_out' => [
                'sometimes',
                'nullable',
                'date_format:H:i:s',
            ],
            'comment' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];

        // 勤怠日が送信された場合、同じユーザーの日付重複を確認
        if ($this->exists('date') && $attendance) {
            $rules['date'][] = Rule::unique('attendances', 'work_date')
                ->where('user_id', $attendance->user_id)
                ->ignore($attendance->id);
        }

        return $rules;
    }

    /**
     * 更新後の出勤・退勤時刻の前後関係を確認する。
     * 片方の時刻だけが送信された場合も既存値と比較する。
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // 時刻の形式が不正な場合は、前後関係の確認を行わない
            if (
                $validator->errors()->has('clock_in') ||
                $validator->errors()->has('clock_out')
            ) {
                return;
            }

            $attendanceRecord = $this->route('attendanceRecord');

            $attendanceId = is_object($attendanceRecord)
                ? $attendanceRecord->id
                : $attendanceRecord;

            $attendance = Attendance::find($attendanceId);

            if (! $attendance) {
                return;
            }

            // 送信された値を優先し、省略された項目は既存値を使用
            $clockIn = $this->exists('clock_in')
                ? $this->input('clock_in')
                : $attendance->clock_in?->format('H:i:s');

            $clockOut = $this->exists('clock_out')
                ? $this->input('clock_out')
                : $attendance->clock_out?->format('H:i:s');

            // 退勤時刻が未設定（null）なら比較しない
            if ($clockIn !== null && $clockOut !== null) {
                if ($clockOut <= $clockIn) {
                    $validator->errors()->add(
                        'clock_out',
                        '退勤時刻は出勤時刻より後の時刻を指定してください。'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique' => 'この日付の勤怠は既に登録されています。',

            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',

            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',

            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}
