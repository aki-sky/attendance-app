<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class T19_SanctumAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証のPOSTは401になる
     */
    public function test_unauthenticated_user_cannot_create_attendance_record(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * 未認証のPUTは401になる
     */
    public function test_unauthenticated_user_cannot_update_attendance_record(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
        ]);

        $response = $this->putJson(
            '/api/v1/attendance-records/' . $attendance->id,
            ['comment' => '変更']
        );

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * 未認証のDELETEは401になる
     */
    public function test_unauthenticated_user_cannot_delete_attendance_record(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
        ]);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/' . $attendance->id
        );

        $response->assertUnauthorized()
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * 認証済みユーザーは自分の勤怠を更新できる
     */
    public function test_user_can_update_own_attendance_record(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
        ]);

        $response = $this->putJson(
            '/api/v1/attendance-records/' . $attendance->id,
            ['comment' => '自分で更新']
        );

        $response->assertOk()
            ->assertJsonPath('data.comment', '自分で更新');

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'note' => '自分で更新',
        ]);
    }

    /**
     * 認証済みユーザーは自分の勤怠を削除できる
     */
    public function test_user_can_delete_own_attendance_record(): void
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
     * 他ユーザーの勤怠は更新できない
     */
    public function test_user_cannot_update_another_users_attendance_record(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $anotherUser->id,
            'work_date' => '2026-10-01',
        ]);

        $response = $this->putJson(
            '/api/v1/attendance-records/' . $attendance->id,
            ['comment' => '不正な更新']
        );

        $response->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);
    }

    /**
     * 他ユーザーの勤怠は削除できない
     */
    public function test_user_cannot_delete_another_users_attendance_record(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        Sanctum::actingAs($user);

        $attendance = Attendance::factory()->create([
            'user_id' => $anotherUser->id,
            'work_date' => '2026-10-01',
        ]);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/' . $attendance->id
        );

        $response->assertForbidden()
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
        ]);
    }
}
