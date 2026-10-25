<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T03_AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_required_for_admin_login(): void
    {
        User::factory()->create([
            'role' => 1,
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required_for_admin_login(): void
    {
        $user = User::factory()->create([
            'role' => 1,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_login_fails_with_unregistered_email_for_admin(): void
    {
        User::factory()->create([
            'role' => 1,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'not-registered@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
