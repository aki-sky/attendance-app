<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class T18_PublicApiWriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * POSTで勤怠を登録できる
     */
    public function test_attendance_record_can_be_created(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'APIテスト',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.date', '2026-10-01')
            ->assertJsonPath('data.clock_in', '09:00:00')
            ->assertJsonPath('data.clock_out', '18:00:00')
            ->assertJsonPath('data.comment', 'APIテスト');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
            'note' => 'APIテスト',
        ]);
    }

    /**
     * 必須項目がない場合は422と日本語エラーが返る
     */
    public function test_attendance_record_creation_returns_validation_errors(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'user_id',
                'date',
                'clock_in',
            ])
            ->assertJsonPath(
                'errors.date.0',
                '勤怠日は必須です。'
            )
            ->assertJsonPath(
                'errors.clock_in.0',
                '出勤時刻は必須です。'
            );
    }

    /**
     * PUTで既存の勤怠を更新できる
     */
    public function test_attendance_record_can_be_updated(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
            'clock_in' => '2026-10-01 09:00:00',
            'clock_out' => '2026-10-01 18:00:00',
            'note' => '変更前',
        ]);

        $response = $this->putJson(
            '/api/v1/attendance-records/' . $attendance->id,
            [
                'date' => '2026-10-01',
                'clock_in' => '09:30:00',
                'clock_out' => '18:30:00',
                'comment' => '変更後',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.clock_in', '09:30:00')
            ->assertJsonPath('data.clock_out', '18:30:00')
            ->assertJsonPath('data.comment', '変更後');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_date' => '2026-10-01',
            'note' => '変更後',
        ]);
    }

    /**
     * 存在しない勤怠IDの更新は404になる
     */
    public function test_updating_missing_attendance_record_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson(
            '/api/v1/attendance-records/99999',
            [
                'date' => '2026-10-01',
                'clock_in' => '09:00:00',
            ]
        );

        $response->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    /**
     * DELETEで勤怠を削除できる
     */
    public function test_attendance_record_can_be_deleted(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
        ]);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/' . $attendance->id
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    /**
     * 存在しない勤怠IDの削除は404になる
     */
    public function test_deleting_missing_attendance_record_returns_404(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/99999'
        );

        $response->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
