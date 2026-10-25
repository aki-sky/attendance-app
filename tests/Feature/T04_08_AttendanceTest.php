<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class T04_08_AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 勤怠登録画面
    // ========================================

    /** 日付・時刻が表示される */
    public function test_attendance_register_displays_current_date_and_time(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 9, 30, 10, 30, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertStatus(200);

        $response->assertViewHas('formattedDate', '2026年09月30日');
        $response->assertViewHas('formattedTime', '10:30');
    }

    // ========================================
    // 勤務状態
    // ========================================

    /** 勤務外の場合 */
    public function test_attendance_status_is_off_duty(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '勤務外';
        });
    }

    /** 出勤中の場合 */
    public function test_attendance_status_is_working(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    /** 休憩中の場合 */
    public function test_attendance_status_is_on_break(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'rest_start' => now(),
            'rest_end' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '休憩中';
        });
    }

    /** 退勤済の場合 */
    public function test_attendance_status_is_clocked_out(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '退勤済';
        });
    }

    // ========================================
    // 出勤
    // ========================================

    /** 出勤ボタンを押すと出勤中になる */
    public function test_clock_in_button_is_displayed_and_status_becomes_working(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '勤務外';
        });

        $response->assertSee(
            '<button class="attendance__button--submit--clock-in" type="submit" name="action" value="clock_in">出勤</button>',
            false
        );

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        $response->assertRedirect(route('attendance.create'));

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    /** 退勤済の場合は出勤ボタンが表示されない */
    public function test_clock_in_button_is_not_displayed_when_user_has_clocked_out(): void
    {
        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now(),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '退勤済';
        });

        $response->assertDontSee('出勤');
    }

    /** 出勤時刻が正しく記録される */
    public function test_clock_in_time_is_recorded_correctly_in_attendance_list(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 9, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 1
                && $records->first()['clock_in'] === '09:00';
        });
    }

    // ========================================
    // 休憩
    // ========================================

    /** 出勤中は休憩入ボタンが表示され、休憩開始すると休憩中になる */
    public function test_break_in_button_is_displayed_and_status_becomes_on_break(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(3),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee(
            '<button class="attendance__button--submit--break-in" type="submit" name="action" value="break_in">休憩入</button>',
            false
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '休憩中';
        });

        $response->assertSee(
            '<button class="attendance__button--submit--break-out" type="submit" name="action" value="break_out">休憩戻</button>',
            false
        );
    }

    /** 休憩終了後は休憩入ボタンが再び表示される */
    public function test_break_in_button_is_displayed_again_after_break_out(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(3),
            'clock_out' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 13, 0, 0)
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });

        $response->assertSee(
            '<button class="attendance__button--submit--break-in" type="submit" name="action" value="break_in">休憩入</button>',
            false
        );
    }

    /** 休憩中は休憩戻ボタンが表示され、休憩終了後は出勤中になる */
    public function test_break_out_button_is_displayed_and_status_becomes_working_after_break_out(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(3),
            'clock_out' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee(
            '<button class="attendance__button--submit--break-out" type="submit" name="action" value="break_out">休憩戻</button>',
            false
        );

        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 13, 0, 0)
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });
    }

    /** 2回目の休憩でも休憩戻ボタンが表示される */
    public function test_break_out_button_is_displayed_when_user_takes_a_second_break(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(3),
            'clock_out' => null,
        ]);

        // 1回目
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 13, 0, 0)
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        // 2回目
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 15, 0, 0)
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertSee(
            '<button class="attendance__button--submit--break-out" type="submit" name="action" value="break_out">休憩戻</button>',
            false
        );
    }

    /** 休憩時間が正しく記録される */
    public function test_break_times_are_recorded_correctly_in_attendance_list(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => today(),
            'clock_in' => now()->subHours(3),
            'clock_out' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 13, 0, 0)
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        $this->assertDatabaseHas('rests', [
            'attendance_id' => $user->attendances()->first()->id,
            'rest_start' => '2026-10-05 12:00:00',
            'rest_end' => '2026-10-05 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 1
                && $records->first()['total_break_time'] === 3600;
        });
    }

    // ========================================
// 退勤
// ========================================

    /** 退勤ボタンが表示され、退勤後に退勤済になる */
    public function test_clock_out_button_is_displayed_and_status_becomes_clocked_out(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 9, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        // 出勤
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 勤務中の画面を確認
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '出勤中';
        });

        $response->assertSee(
            '<button class="attendance__button--submit--clock-out" type="submit" name="action" value="clock_out">退勤</button>',
            false
        );

        // 退勤
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 18, 0, 0)
        );

        $response = $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        $response->assertRedirect(route('attendance.create'));

        // 退勤後の画面を確認
        $response = $this->actingAs($user)
            ->get('/attendance');

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->attendance_status === '退勤済';
        });
    }

    /** 退勤時刻が勤怠一覧に正確に記録される */
    public function test_clock_out_time_is_recorded_correctly_in_attendance_list(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 9, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        // 出勤
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 退勤
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 18, 0, 0)
        );

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        // DBに退勤時刻が正しく保存されていることを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        // 勤怠一覧画面を確認
        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 1
                && $records->first()['clock_in'] === '09:00'
                && $records->first()['clock_out'] === '18:00';
        });
    }
}
