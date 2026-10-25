<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestRestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_break_in' => [
                'nullable',
                'array',
            ],

            'new_break_in.*' => [
                'nullable',
                'date_format:H:i',
                'after:new_clock_in',
                'before:new_clock_out',
            ],

            'new_break_out' => [
                'nullable',
                'array',
            ],

            'new_break_out.*' => [
                'nullable',
                'date_format:H:i',
                'before:new_clock_out',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'new_break_in.array' => '休憩開始時刻の形式が不正です',
            'new_break_in.*.date_format' => '休憩開始時刻は時刻形式で入力してください',
            'new_break_in.*.after' => '休憩時間が不適切な値です',
            'new_break_in.*.before' => '休憩時間が不適切な値です',

            'new_break_out.array' => '休憩終了時刻の形式が不正です',
            'new_break_out.*.date_format' => '休憩終了時刻は時刻形式で入力してください',
            'new_break_out.*.before' => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $breakIns = $this->input('new_break_in', []);
            $breakOuts = $this->input('new_break_out', []);

            foreach ($breakOuts as $index => $breakOut) {

                $breakIn = $breakIns[$index] ?? null;

                // どちらかが空ならここではチェックしない
                if (!$breakIn || !$breakOut) {
                    continue;
                }

                // 休憩終了が休憩開始以前ならエラー
                if (strtotime($breakOut) <= strtotime($breakIn)) {
                    $validator->errors()->add(
                        "new_break_out.$index",
                        '休憩時間が不適切な値です'
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        foreach (['new_break_in', 'new_break_out'] as $field) {
            $values = $this->input($field, []);

            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $index => $value) {
                if (
                    is_string($value)
                    && preg_match('/^(\d{1,2}):(\d{2})$/', $value, $matches)
                ) {
                    $values[$index] = sprintf(
                        '%02d:%02d',
                        $matches[1],
                        $matches[2]
                    );
                }
            }

            $this->merge([$field => $values]);
        }
    }
}
