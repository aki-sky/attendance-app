<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\RequestAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T15_AdminApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者の申請一覧に全ユーザーの承認待ち申請が表示される
     */
    public function test_admin_can_view_all_pending_applications(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user1 = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤花子',
            'role' => 0,
        ]);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'work_date' => '2026-10-06',
            'clock_in' => '2026-10-06 09:00:00',
            'clock_out' => '2026-10-06 18:00:00',
        ]);

        RequestAttendance::factory()->create([
            'attendance_id' => $attendance1->id,
            'status' => 0,
            'request_note' => '山田の修正申請',
        ]);

        RequestAttendance::factory()->create([
            'attendance_id' => $attendance2->id,
            'status' => 0,
            'request_note' => '佐藤の修正申請',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-application-list');
        $response->assertSee('山田太郎');
        $response->assertSee('佐藤花子');
        $response->assertSee('山田の修正申請');
        $response->assertSee('佐藤の修正申請');
        $response->assertViewHas('applications', function ($applications) {
            return $applications->count() === 2
                && $applications->every(
                    fn($application) => $application->status === 0
                );
        });
    }

    /**
     * 管理者の申請一覧に全ユーザーの承認済み申請が表示される
     */
    public function test_admin_can_view_all_approved_applications(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user1 = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤花子',
            'role' => 0,
        ]);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'work_date' => '2026-10-06',
            'clock_in' => '2026-10-06 09:00:00',
            'clock_out' => '2026-10-06 18:00:00',
        ]);

        RequestAttendance::factory()->create([
            'attendance_id' => $attendance1->id,
            'status' => 1,
            'request_note' => '山田の承認済み申請',
        ]);

        RequestAttendance::factory()->create([
            'attendance_id' => $attendance2->id,
            'status' => 1,
            'request_note' => '佐藤の承認済み申請',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-application-list');
        $response->assertSee('山田太郎');
        $response->assertSee('佐藤花子');
        $response->assertSee('山田の承認済み申請');
        $response->assertSee('佐藤の承認済み申請');

        $response->assertViewHas('applications', function ($applications) {
            return $applications->count() === 2
                && $applications->every(
                    fn($application) => $application->status === 1
                );
        });
    }

    /**
     * 管理者が修正申請の詳細画面で申請内容を確認できる
     */
    public function test_admin_can_view_application_detail_correctly(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $staff = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $application = RequestAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-10-05 09:30:00',
            'request_clock_out' => '2026-10-05 18:30:00',
            'request_note' => '勤怠を修正してください',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/stamp_correction_request/approve/' . $application->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-application-detail');
        $response->assertSee('山田太郎');
        $response->assertSee('勤怠を修正してください');
        $response->assertSee('09:30');
        $response->assertSee('18:30');
    }

    /**
     * 管理者が修正申請を承認すると勤怠情報が更新される
     */
    public function test_admin_can_approve_application_and_update_attendance(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $staff = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
            'note' => '修正前の備考',
        ]);

        $application = RequestAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-10-05 09:30:00',
            'request_clock_out' => '2026-10-05 18:30:00',
            'request_note' => '勤怠を修正してください',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->post('/stamp_correction_request/approve/' . $application->id);

        $response->assertRedirect();

        $this->assertDatabaseHas('request_attendances', [
            'id' => $application->id,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2026-10-05 09:30:00',
            'clock_out' => '2026-10-05 18:30:00',
            'note' => '勤怠を修正してください',
        ]);
    }
}
