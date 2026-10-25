<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください',
            'name.string' => '文字で入力してください',
            'name.max' => '225文字以下で入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.string' => '文字で入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'email.max' => '225文字以下で入力してください',
            'email.unique' => 'このメールアドレスは既に使用されています',
            'password.required' => 'パスワードを入力してください',
            'password.string' => '文字で入力してください',
            'password.confirmed' => 'パスワードと一致しません',
            'password.min' => 'パスワードは8文字以上で入力してください',
        ];
    }
}
