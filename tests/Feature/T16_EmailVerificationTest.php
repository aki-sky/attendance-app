<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class T16_EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    /** 登録時に認証メールが送信される */
    public function test_user_receives_email_verification_notification_after_registration(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();

        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /** 未認証ユーザーに認証案内画面が表示される */
    public function test_unverified_user_can_view_email_verification_prompt(): void
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertOk();
        $response->assertSee('メール認証を完了してください');
        $response->assertSee('http://localhost:8025');
        $response->assertSee('認証はこちらから');
    }

    /** 認証済みユーザーは勤怠登録画面にアクセスできる */
    public function test_verified_user_can_access_attendance_registration_page(): void
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
    }

    /** メール認証を完了すると認証済みになり、勤怠登録画面へ遷移する */
    public function test_user_can_verify_email_and_proceed_to_attendance_page(): void
    {
        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => null,
        ]);

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // メール認証が完了したこと
        $this->assertNotNull($user->fresh()->email_verified_at);

        // 勤怠登録画面へ遷移すること
        $response->assertRedirect('/attendance?verified=1');
    }
}
