<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                'in:clock_in,clock_out,break_in,break_out',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => '勤怠操作を選択してください',
            'action.string' => '勤怠操作が不正です',
            'action.in' => '勤怠操作が不正です',
        ];
    }
}
