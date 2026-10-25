<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class T20_ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** 未認証の場合はログイン画面にリダイレクトされる */
    public function test_guest_is_redirected_to_login_from_report(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    /** 勤怠データがある場合、各統計が正しく集計される */
    public function test_report_statistics_are_calculated_correctly(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        // 9時間勤務：残業1時間
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
            'clock_in' => '2026-10-01 09:00:00',
            'clock_out' => '2026-10-01 18:00:00',
        ]);

        // 9時間勤務：遅刻、残業1時間
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-02',
            'clock_in' => '2026-10-02 09:30:00',
            'clock_out' => '2026-10-02 18:30:00',
        ]);

        // 9時間勤務：遅刻、残業1時間
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-03',
            'clock_in' => '2026-10-03 10:00:00',
            'clock_out' => '2026-10-03 19:00:00',
        ]);

        // 9時間勤務：早退、残業1時間
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-04',
            'clock_in' => '2026-10-04 08:00:00',
            'clock_out' => '2026-10-04 17:00:00',
        ]);

        // 11時間勤務：残業3時間、長時間労働
        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-05',
            'clock_in' => '2026-10-05 08:00:00',
            'clock_out' => '2026-10-05 19:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertOk();

        // 総労働時間47時間、残業7時間、平均9時間24分
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_work_minutes'] === 47 * 60
                && $summary['total_overtime_minutes'] === 7 * 60
                && $summary['avg_work_minutes'] === 9 * 60 + 24;
        });

        // 当月の月別集計
        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) {
            foreach ($monthlyTrend as $month) {
                if ($month['month'] === '2026-10') {
                    return $month['work_minutes'] === 47 * 60
                        && $month['overtime_minutes'] === 7 * 60;
                }
            }

            return false;
        });

        // 遅刻2回、早退1回、長時間労働1回
        $response->assertViewHas('anomalies', function ($anomalies) {
            return $anomalies['late_count'] === 2
                && $anomalies['early_leave_count'] === 1
                && $anomalies['long_work_count'] === 1;
        });
    }

    /** 勤怠データがない場合、各統計が0になる */
    public function test_report_returns_zero_statistics_when_no_attendance_exists(): void
    {
        Carbon::setTestNow(
            Carbon::create(2026, 10, 5, 12, 0, 0)
        );

        $user = User::factory()->create([
            'role' => 0,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertOk();

        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_work_minutes'] === 0
                && $summary['total_overtime_minutes'] === 0
                && $summary['avg_work_minutes'] === 0;
        });

        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) {
            if (count($monthlyTrend) !== 6) {
                return false;
            }

            foreach ($monthlyTrend as $month) {
                if (
                    $month['work_minutes'] !== 0
                    || $month['overtime_minutes'] !== 0
                ) {
                    return false;
                }
            }

            return true;
        });

        $response->assertViewHas('anomalies', function ($anomalies) {
            return $anomalies['late_count'] === 0
                && $anomalies['early_leave_count'] === 0
                && $anomalies['long_work_count'] === 0;
        });
    }
}
