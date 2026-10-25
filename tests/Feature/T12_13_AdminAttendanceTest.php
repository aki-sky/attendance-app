<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T12_13_AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者の勤怠一覧にその日の全ユーザーの勤怠情報が表示される
     */
    public function test_admin_can_view_all_users_attendance_for_selected_day(): void
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
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:30:00',
            'clock_out' => '2026-10-05 17:30:00',
        ]);

        // 別の日の勤怠は一覧に含まれない
        Attendance::factory()->create([
            'user_id' => $user1->id,
            'work_date' => '2026-10-04',
            'clock_in' => '2026-10-04 08:00:00',
            'clock_out' => '2026-10-04 17:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/list?date=2026-10-05');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');

        $response->assertViewHas('attendanceRecords', function ($records) use (
            $attendance1,
            $attendance2
        ) {
            return $records->count() === 2
                && $records->contains(function ($record) use ($attendance1) {
                    return $record->id === $attendance1->id
                        && $record->clock_in->format('H:i') === '09:00'
                        && $record->clock_out->format('H:i') === '18:00';
                })
                && $records->contains(function ($record) use ($attendance2) {
                    return $record->id === $attendance2->id
                        && $record->clock_in->format('H:i') === '09:30'
                        && $record->clock_out->format('H:i') === '17:30';
                });
        });
    }

    /**
     * 管理者の勤怠一覧に対象日の日付が表示される
     */
    public function test_admin_attendance_list_displays_selected_date(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/list?date=2026-10-05');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');
        $response->assertSee('2026/10/05');
    }

    /**
     * 管理者の勤怠一覧で前日の勤怠情報が表示される
     */
    public function test_admin_can_view_previous_day_attendance(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        $previousAttendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-04',
            'clock_in' => '2026-10-04 09:00:00',
            'clock_out' => '2026-10-04 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 10:00:00',
            'clock_out' => '2026-10-05 19:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/list?date=2026-10-04');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-10-04';
        });

        $response->assertViewHas('attendanceRecords', function ($records) use ($previousAttendance) {
            return $records->count() === 1
                && $records->contains(function ($record) use ($previousAttendance) {
                    return $record->id === $previousAttendance->id
                        && $record->clock_in->format('H:i') === '09:00'
                        && $record->clock_out->format('H:i') === '18:00';
                });
        });
    }

    /**
     * 管理者の勤怠一覧で翌日の勤怠情報が表示される
     */
    public function test_admin_can_view_next_day_attendance(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $nextAttendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-06',
            'clock_in' => '2026-10-06 10:00:00',
            'clock_out' => '2026-10-06 19:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/list?date=2026-10-06');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-attendance-list');

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m-d') === '2026-10-06';
        });

        $response->assertViewHas('attendanceRecords', function ($records) use ($nextAttendance) {
            return $records->count() === 1
                && $records->contains(function ($record) use ($nextAttendance) {
                    return $record->id === $nextAttendance->id
                        && $record->clock_in->format('H:i') === '10:00'
                        && $record->clock_out->format('H:i') === '19:00';
                });
        });
    }
    /**
     * 管理者の勤怠詳細画面に選択した勤怠情報が表示される
     */
    public function test_admin_can_view_attendance_detail_with_correct_data(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
            'note' => '勤怠詳細の確認テスト',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-detail');
        $response->assertViewHas('user', function ($viewUser) use ($user) {
            return $viewUser->id === $user->id
                && $viewUser->name === '山田太郎';
        });

        $response->assertViewHas('attendanceRecord', function ($record) use ($attendance) {
            return $record['id'] === $attendance->id
                && $record['year'] === '2026年'
                && $record['date'] === '10月5日'
                && $record['clock_in'] === '09:00'
                && $record['clock_out'] === '18:00'
                && $record['comment'] === '勤怠詳細の確認テスト';
        });
    }

    /**
     * 管理者が出勤時間を退勤時間より後にするとバリデーションエラーになる
     */
    public function test_admin_cannot_set_clock_in_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->from('/attendance/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '19:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [],
                'new_break_out' => [],
                'comment' => '管理者の勤怠修正テスト',
            ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);
    }

    /**
     * 管理者が休憩開始時間を退勤時間より後にするとバリデーションエラーになる
     */
    public function test_admin_cannot_set_break_start_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->from('/attendance/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['19:00'],
                'new_break_out' => ['19:30'],
                'comment' => '管理者の休憩時間修正テスト',
            ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);
    }

    /**
     * 管理者が休憩終了時間を退勤時間より後にするとバリデーションエラーになる
     */
    public function test_admin_cannot_set_break_end_after_clock_out(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->from('/attendance/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['19:00'],
                'comment' => '管理者の休憩時間修正テスト',
            ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);
    }

    /**
     * 管理者が備考を未入力で保存するとバリデーションエラーになる
     */
    public function test_admin_cannot_save_attendance_with_empty_comment(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
            'note' => '修正前の備考',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->from('/attendance/' . $attendance->id)
            ->post('/attendance/' . $attendance->id, [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => [],
                'new_break_out' => [],
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'note' => '修正前の備考',
        ]);
    }
}
