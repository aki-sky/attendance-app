<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class T10_AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 勤怠詳細
    // ========================================

    /** 名前がログインユーザーの名前になっている */
    public function test_attendance_detail_displays_logged_in_user_name(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 18, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
            'name' => '山田太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance->id);

        $response->assertStatus(200);

        $response->assertViewHas('user', function ($viewUser) {
            return $viewUser->name === '山田太郎';
        });
    }

    /** 日付が選択した日付になっている */
    public function test_attendance_detail_displays_selected_date(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 18, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-03',
            'clock_in' => '2026-10-03 09:00:00',
            'clock_out' => '2026-10-03 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance->id);

        $response->assertStatus(200);

        $response->assertViewHas('data', function ($data) {
            return $data['year'] === '2026年'
                && $data['date'] === '10月3日';
        });
    }

    /** 出勤・退勤時間がログインユーザーの打刻と一致している */
    public function test_attendance_detail_displays_correct_clock_in_and_clock_out(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 18, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:15:00',
            'clock_out' => '2026-10-05 18:30:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance->id);

        $response->assertStatus(200);

        $response->assertViewHas('data', function ($data) {
            return $data['clock_in'] === '09:15'
                && $data['clock_out'] === '18:30';
        });
    }

    /** 休憩時間がログインユーザーの打刻と一致している */
    public function test_attendance_detail_displays_correct_break_times(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 18, 30, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 09:00:00',
            'clock_out' => '2026-10-05 18:00:00',
        ]);

        Rest::factory()->create([
            'attendance_id' => $attendance->id,
            'rest_start' => '2026-10-05 12:00:00',
            'rest_end' => '2026-10-05 13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance->id);

        $response->assertStatus(200);

        $response->assertViewHas('data', function ($data) {
            return $data['breaks']->count() === 1
                && $data['breaks']->first()['break_in'] === '12:00'
                && $data['breaks']->first()['break_out'] === '13:00';
        });
    }
}
