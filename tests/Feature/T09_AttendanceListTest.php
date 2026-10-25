<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class T09_AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // 勤怠一覧
    // ========================================

    /** 自分の勤怠情報がすべて表示される */
    public function test_user_can_see_all_of_their_attendance_records(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        // 自分の勤怠
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
            'clock_in' => '2026-10-01 09:00:00',
            'clock_out' => '2026-10-01 18:00:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-02',
            'clock_in' => '2026-10-02 09:30:00',
            'clock_out' => '2026-10-02 18:30:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-03',
            'clock_in' => '2026-10-03 10:00:00',
            'clock_out' => '2026-10-03 19:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 3
                && $records[0]['clock_in'] === '09:00'
                && $records[0]['clock_out'] === '18:00'
                && $records[1]['clock_in'] === '09:30'
                && $records[1]['clock_out'] === '18:30'
                && $records[2]['clock_in'] === '10:00';
        });

        // 別ユーザーの勤怠
        $otherUser = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-10-04',
            'clock_in' => '2026-10-04 08:00:00',
            'clock_out' => '2026-10-04 17:00:00',
        ]);
    }

    /** 現在の月が表示される */
    public function test_current_month_is_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y/m') === '2026/10';
        });
    }

    /** 前月の情報が表示される */
    public function test_previous_month_is_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-09-15',
            'clock_in' => '2026-09-15 09:00:00',
            'clock_out' => '2026-09-15 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y/m') === '2026/09';
        });

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 1
                && $records->first()['date'] === '09/15（火）';
        });
    }

    /** 翌月の情報が表示される */
    public function test_next_month_is_displayed(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-11-15',
            'clock_in' => '2026-11-15 09:00:00',
            'clock_out' => '2026-11-15 18:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-11');

        $response->assertStatus(200);

        $response->assertViewHas('date', function ($date) {
            return $date->format('Y/m') === '2026/11';
        });

        $response->assertViewHas('formattedAttendanceRecords', function ($records) {
            return $records->count() === 1
                && $records->first()['date'] === '11/15（日）';
        });
    }

    /** 詳細ボタンからその日の勤怠詳細画面に遷移できる */
    public function test_user_can_go_to_attendance_detail_from_list(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
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

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee(
            '/attendance/' . $attendance->id,
            false
        );

        $response = $this->actingAs($user)
            ->get('/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertViewHas('data', function ($data) use ($attendance) {
            return $data['id'] === $attendance->id;
        });
    }
}
