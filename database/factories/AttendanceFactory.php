<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $clockIn = fake()->dateTimeBetween(
            'today 08:00:00',
            'today 09:00:00'
        );

        $clockOut = fake()->dateTimeBetween(
            'today 17:00:00',
            'today 18:00:00'
        );

        return [
            'user_id' => User::factory(),
            'work_date' => $clockIn->format('Y-m-d'),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'note' => fake()->optional()->sentence(5),
        ];
    }
}
