<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_clock_in' => [
                'required',
                'date_format:H:i',
                'before:new_clock_out',
            ],

            'new_clock_out' => [
                'required',
                'date_format:H:i',
            ],

            'comment' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_clock_in.required' => '出勤時刻を入力してください',
            'new_clock_in.date_format' => '出勤時刻は時刻形式で入力してください',
            'new_clock_in.before' => '出勤時間もしくは退勤時間が不適切な値です',

            'new_clock_out.date_format' => '退勤時刻は時刻形式で入力してください',
            'new_clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',

            'comment.required' => '備考を記入してください',
            'comment.string' => '備考は文字で入力してください',
            'comment.max' => '備考は255文字以下で入力してください',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['new_clock_in', 'new_clock_out'] as $field) {
            $value = $this->input($field);

            if (
                is_string($value)
                && preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches)
            ) {
                $this->merge([
                    $field => sprintf('%02d:%02d', $matches[1], $matches[2]),
                ]);
            }
        }
    }
}
