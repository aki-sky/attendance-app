<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Attendance;

class T14_AdminStaffTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者がスタッフ一覧で全ての一般ユーザーの氏名とメールアドレスを確認できる
     */
    public function test_admin_can_view_all_general_users_name_and_email(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);
        $user1 = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'role' => 0,
        ]);

        $user2 = User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
            'role' => 0,
        ]);

        // 管理者以外の管理者ユーザーも作成し、一覧に混ざらないことを確認する
        User::factory()->create([
            'name' => '管理者ユーザー',
            'email' => 'other-admin@example.com',
            'role' => 1,
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff-list');
        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');
        $response->assertDontSee('other-admin@example.com');
    }

    /**
     * 管理者が選択したスタッフの勤怠一覧を正しく確認できる
     */
    public function test_admin_can_view_selected_staff_attendance_list(): void
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

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/staff/' . $staff->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff-attendance-list');
        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 管理者がスタッフの勤怠一覧で前月の情報を確認できる
     */
    public function test_admin_can_view_previous_month_staff_attendance(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $staff = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2026-09-05',
            'clock_in' => '2026-09-05 09:00:00',
            'clock_out' => '2026-09-05 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/staff/' . $staff->id . '?date=2026-09');

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff-attendance-list');

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m') === '2026-09';
        });

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return collect($records)->contains(function ($record) {
                return str_starts_with($record['date'], '09/05');
            });
        });
    }

    /**
     * 管理者がスタッフの勤怠一覧で翌月の情報を確認できる
     */
    public function test_admin_can_view_next_month_staff_attendance(): void
    {
        $admin = User::factory()->create([
            'role' => 1,
        ]);

        $staff = User::factory()->create([
            'name' => '山田太郎',
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $staff->id,
            'work_date' => '2026-11-05',
            'clock_in' => '2026-11-05 09:00:00',
            'clock_out' => '2026-11-05 18:00:00',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/staff/' . $staff->id . '?date=2026-11');

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff-attendance-list');

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y-m') === '2026-11';
        });

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return collect($records)->contains(function ($record) {
                return str_starts_with($record['date'], '11/05');
            });
        });
    }

    /**
     * 管理者がスタッフの勤怠一覧から勤怠詳細画面を開ける
     */
    public function test_admin_can_open_attendance_detail_from_staff_list(): void
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
            'note' => '詳細画面の確認テスト',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/admin/attendance/staff/' . $staff->id);

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff-attendance-list');

        $response->assertViewHas('formattedAttendanceRecords', function ($records) use ($attendance) {
            return collect($records)->contains(function ($record) use ($attendance) {
                return $record['id'] === $attendance->id;
            });
        });

        $detailResponse = $this->actingAs($admin, 'web')
            ->get('/attendance/' . $attendance->id);

        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('admin.admin-detail');
        $detailResponse->assertViewHas('attendanceRecord', function ($record) use ($attendance) {
            return $record['id'] === $attendance->id
                && $record['clock_in'] === '09:00'
                && $record['clock_out'] === '18:00';
        });
    }
}
