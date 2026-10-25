<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T02_LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレス未入力の場合、
     * バリデーションエラーになる
     */
    public function test_email_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワード未入力の場合、
     * バリデーションエラーになる
     */
    public function test_password_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 登録されていないメールアドレスでログインした場合、
     * ログインに失敗する
     */
    public function test_login_fails_with_unregistered_email(): void
    {
        User::factory()->create();

        $response = $this->post('/login', [
            'email' => 'not-registered@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
