<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class T17_PublicApiReadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠一覧をJSONで取得でき、ページネーション情報が含まれる
     */
    public function test_attendance_records_index_returns_json_with_pagination(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 3) as $day) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => sprintf('2026-10-%02d', $day),
            ]);
        }

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'user_name',
                        'date',
                        'clock_in',
                        'clock_out',
                        'total_time',
                        'total_break_time',
                        'comment',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ])
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 20);
    }

    /**
     * 勤怠詳細にユーザー・休憩・修正申請の情報が含まれる
     */
    public function test_attendance_record_show_returns_detail_with_relations(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => '2026-10-01',
            'clock_in' => '2026-10-01 09:00:00',
            'clock_out' => '2026-10-01 18:00:00',
        ]);

        $response = $this->getJson(
            '/api/v1/attendance-records/' . $attendance->id
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $attendance->id)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', $user->name)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user' => ['id', 'name'],
                    'date',
                    'clock_in',
                    'clock_out',
                    'breaks',
                    'applications',
                    'comment',
                ],
            ]);
    }

    /**
     * 存在しない勤怠IDでは404とエラーJSONが返る
     */
    public function test_attendance_record_show_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertNotFound()
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
