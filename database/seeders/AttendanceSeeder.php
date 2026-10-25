<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // ユーザー1（レポート集計用）
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();

        // ユーザー2
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();

        // 管理者
        $admin = User::where('email', 'user3@example.com')->firstOrFail();

        /*
         * ユーザー1
         * 過去5か月（2026年5月～9月）は各月15営業日
         * 09:00～18:00、休憩1時間
         */
        for ($monthOffset = 5; $monthOffset >= 1; $monthOffset--) {
            $month = Carbon::create(2026, 10, 1)->subMonths($monthOffset);
            $created = 0;

            for (
                $day = $month->copy()->startOfMonth();
                $day->month === $month->month && $created < 15;
                $day->addDay()
            ) {

                // 土日を除く
                if ($day->isWeekend()) {
                    continue;
                }

                $date = $day->format('Y-m-d');

                Attendance::create([
                    'user_id' => $user1->id,
                    'work_date' => $date,
                    'clock_in' => $date . ' 09:00:00',
                    'clock_out' => $date . ' 18:00:00',
                    'note' => null,
                ]);

                $created++;
            }
        }

        /*
         * ユーザー1
         * 2026年10月の17営業日分
         *
         * 1～10日目：通常勤務 10日
         * 11～13日目：残業 3日
         * 14～15日目：遅刻 2日
         * 16日目：早退 1日
         * 17日目：長時間労働 1日
         */
        $patterns = [];

        // 通常勤務：09:00～18:00（10日）
        for ($i = 0; $i < 10; $i++) {
            $patterns[] = [
                'in' => '09:00:00',
                'out' => '18:00:00',
                'note' => null,
            ];
        }

        // 残業：09:00～20:00（3日）
        for ($i = 0; $i < 3; $i++) {
            $patterns[] = [
                'in' => '09:00:00',
                'out' => '20:00:00',
                'note' => '残業',
            ];
        }

        // 遅刻：09:30～18:00（2日）
        for ($i = 0; $i < 2; $i++) {
            $patterns[] = [
                'in' => '09:30:00',
                'out' => '18:00:00',
                'note' => '遅刻',
            ];
        }

        // 早退：09:00～17:00（1日）
        $patterns[] = [
            'in' => '09:00:00',
            'out' => '17:00:00',
            'note' => '早退',
        ];

        // 長時間労働：08:00～21:00（1日）
        $patterns[] = [
            'in' => '08:00:00',
            'out' => '21:00:00',
            'note' => '長時間労働',
        ];

        $month = Carbon::create(2026, 10, 1);
        $created = 0;

        for (
            $day = $month->copy()->startOfMonth();
            $day->month === $month->month && $created < count($patterns);
            $day->addDay()
        ) {

            // 土日を除く
            if ($day->isWeekend()) {
                continue;
            }

            $date = $day->format('Y-m-d');
            $pattern = $patterns[$created];

            Attendance::create([
                'user_id' => $user1->id,
                'work_date' => $date,
                'clock_in' => $date . ' ' . $pattern['in'],
                'clock_out' => $date . ' ' . $pattern['out'],
                'note' => $pattern['note'],
            ]);

            $created++;
        }

        /*
         * ユーザー2・管理者
         * 一覧や勤怠管理画面を確認するためのサンプルデータ
         */
        foreach ([$user2, $admin] as $user) {
            foreach (['2026-10-20', '2026-10-21', '2026-10-22'] as $date) {
                Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date,
                    'clock_in' => $date . ' 09:00:00',
                    'clock_out' => $date . ' 18:00:00',
                    'note' => null,
                ]);
            }
        }
    }
}
