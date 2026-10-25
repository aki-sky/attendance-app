<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T11_ApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. 一般ユーザーの申請一覧が表示される
     */
    public function test_user_can_view_application_list(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('user.user-application-list');
    }

    /**
     * 2. 管理者の申請一覧が表示される
     */
    public function test_admin_can_view_application_list(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-application-list');
    }

    /**
     * 出勤時間が退勤時間より後の場合、エラーになる
     */
    public function test_clock_in_after_clock_out_shows_validation_error(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($user, 'web')
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'comment' => '勤怠を修正します',
            ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 備考が未入力の場合、エラーになる
     */
    public function test_empty_comment_shows_validation_error(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($user, 'web')
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:30',
                'new_clock_out' => '18:30',
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後の場合、エラーになる
     */
    public function test_break_start_after_clock_out_shows_validation_error(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($user, 'web')
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['19:00'],
                'new_break_out' => ['19:30'],
                'comment' => '休憩時間を修正します',
            ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後の場合、エラーになる
     */
    public function test_break_end_after_clock_out_shows_validation_error(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($user, 'web')
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['19:00'],
                'comment' => '休憩時間を修正します',
            ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 勤怠修正申請が管理者の申請一覧に表示される
     */
    public function test_correction_application_is_displayed_to_admin(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        \App\Models\RequestAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-10-05 09:30:00',
            'request_clock_out' => '2026-10-05 18:30:00',
            'request_note' => '勤怠を修正してください',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-application-list');
        $response->assertSee('勤怠を修正してください');
    }

    /**
     * 管理者が勤怠修正申請の承認画面を開ける
     */
    public function test_admin_can_open_correction_application_approval_screen(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $application = \App\Models\RequestAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-10-05 09:30:00',
            'request_clock_out' => '2026-10-05 18:30:00',
            'request_note' => '承認画面の確認テスト',
            'status' => 0,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/stamp_correction_request/approve/' . $application->id);

        $response->assertStatus(200);
        $response->assertSee('承認画面の確認テスト');
    }

    /**
     * 一般ユーザーの申請一覧に自分の申請が表示される
     */
    /**
     * 一般ユーザーの申請一覧に自分の申請がすべて表示される
     */
    public function test_user_can_see_own_correction_application(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $otherUser = User::factory()->create([
            'role' => 0,
        ]);

        $attendance1 = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $attendance2 = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-06',
            'clock_in' => '2026-10-06 09:00:00',
            'clock_out' => '2026-10-06 18:00:00',
        ]);

        $otherAttendance = \App\Models\Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 08:00:00',
            'clock_out' => '2026-10-05 17:00:00',
        ]);

        \App\Models\RequestAttendance::factory()->create([
            'attendance_id' => $attendance1->id,
            'request_clock_in' => '2026-10-05 09:30:00',
            'request_clock_out' => '2026-10-05 18:30:00',
            'request_note' => '自分の申請その1',
            'status' => 0,
        ]);

        \App\Models\RequestAttendance::factory()->create([
            'attendance_id' => $attendance2->id,
            'request_clock_in' => '2026-10-06 09:30:00',
            'request_clock_out' => '2026-10-06 18:30:00',
            'request_note' => '自分の申請その2',
            'status' => 0,
        ]);

        \App\Models\RequestAttendance::factory()->create([
            'attendance_id' => $otherAttendance->id,
            'request_clock_in' => '2026-10-05 08:30:00',
            'request_clock_out' => '2026-10-05 17:30:00',
            'request_note' => '他ユーザーの申請',
            'status' => 0,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('user.user-application-list');
        $response->assertSee('自分の申請その1');
        $response->assertSee('自分の申請その2');
        $response->assertDontSee('他ユーザーの申請');
    }

    /**
     * 承認済みの申請が一般ユーザーの申請一覧に表示される
     */
    public function test_user_can_see_approved_correction_application(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        \App\Models\RequestAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-10-05 09:30:00',
            'request_clock_out' => '2026-10-05 18:30:00',
            'request_note' => '承認済みの勤怠修正申請です',
            'status' => 1,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('user.user-application-list');
        $response->assertSee('承認済みの勤怠修正申請です');
    }

    /**
     * 申請一覧の詳細から勤怠詳細画面へ遷移できる
     */
    public function test_user_can_open_attendance_detail_from_application(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = \App\Models\Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $application = \App\Models\RequestAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'request_clock_in' => '2026-10-05 09:30:00',
            'request_clock_out' => '2026-10-05 18:30:00',
            'request_note' => '勤怠詳細への遷移テスト',
            'status' => 0,
        ]);

        $response = $this->actingAs($user, 'web')
            ->get('/application/' . $application->id);

        $response->assertStatus(200);
        $response->assertViewIs('user.user-detail');
    }
}
