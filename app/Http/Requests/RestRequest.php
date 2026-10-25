<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendance_id' => ['required', 'integer', 'exists:attendances,id'],
            'rest_start' => ['required', 'date', 'after:clock_in', 'before:clock_out'],
            'rest_end' => ['date', 'after:rest_start', 'before:clock_out'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_id.required' => 'アテンダンスIDを入力してください',
            'attendance_id.integer' => 'アテンダンスIDは整数で入力してください',
            'attendance_id.exists' => '指定された勤務は存在しません',
            'rest_start.required' => '休憩開始時刻を入力してください',
            'rest_start.date' => '休憩開始時刻は日時形式で入力してください',
            'rest_start.after' => '休憩開始時刻は出勤時刻より後の時間を入力してください',
            'rest_start.before' => '休憩開始時刻は退勤時刻より前の時間を入力してください',
            'rest_end.date' => '休憩終了時刻は日時形式で入力してください',
            'rest_end.after' => '休憩終了時刻は休憩開始時刻より後の時間を入力してください',
            'rest_end.before' => '休憩終了時刻は退勤時刻より前の時間を入力してください',
        ];
    }
}
