<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RequestAttendance>
 */
class RequestAttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $requestClockIn = fake()->dateTimeBetween(
            'today 08:00:00',
            'today 09:00:00'
        );

        $requestClockOut = fake()->dateTimeBetween(
            'today 17:00:00',
            'today 18:00:00'
        );

        return [
            'attendance_id' => Attendance::factory(),
            'request_clock_in' => $requestClockIn,
            'request_clock_out' => $requestClockOut,
            'request_note' => fake()->sentence(5),
            'status' => 0,
            'approved_at' => null,
        ];
    }
}
