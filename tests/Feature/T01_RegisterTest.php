<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T01_RegisterTest extends TestCase
{
    use RefreshDatabase;
    /** * 名前が未入力の場合、バリデーションエラーになる */ public function test_name_is_required(): void
    {
        $response = $this->post('/register', ['name' => '', 'email' => 'test@example.com', 'password' => 'password', 'password_confirmation' => 'password',]);
        $response->assertSessionHasErrors(['name' => 'お名前を入力してください',]);
    }
    /** * メールアドレスが未入力の場合、バリデーションエラーになる */ public function test_email_is_required(): void
    {
        $response = $this->post('/register', ['name' => 'テスト太郎', 'email' => '', 'password' => 'password', 'password_confirmation' => 'password',]);
        $response->assertSessionHasErrors(['email' => 'メールアドレスを入力してください',]);
    }
    /** * パスワードが8文字未満の場合、バリデーションエラーになる */ public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/register', ['name' => 'テスト太郎', 'email' => 'test@example.com', 'password' => '1234567', 'password_confirmation' => '1234567',]);
        $response->assertSessionHasErrors(['password' => 'パスワードは8文字以上で入力してください',]);
    }
    /** * パスワードと確認用パスワードが一致しない場合、バリデーションエラーになる */ public function test_password_confirmation_must_match(): void
    {
        $response = $this->post('/register', ['name' => 'テスト太郎', 'email' => 'test@example.com', 'password' => 'password', 'password_confirmation' => 'different',]);
        $response->assertSessionHasErrors(['password' => 'パスワードと一致しません',]);
    }
    /** * パスワードが未入力の場合、バリデーションエラーになる */ public function test_password_is_required(): void
    {
        $response = $this->post('/register', ['name' => 'テスト太郎', 'email' => 'test@example.com', 'password' => '', 'password_confirmation' => '',]);
        $response->assertSessionHasErrors(['password' => 'パスワードを入力してください',]);
    }
    /** * 正しいユーザー情報を入力すると、ユーザーがDBに登録される */ public function test_user_can_register(): void
    {
        $response = $this->post('/register', ['name' => 'テスト太郎', 'email' => 'test@example.com', 'password' => 'password', 'password_confirmation' => 'password',]);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['name' => 'テスト太郎', 'email' => 'test@example.com',]);
    }
}
