<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rest>
 */
class RestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $restStart = fake()->dateTimeBetween(
            'today 12:00:00',
            'today 13:00:00'
        );

        $restEnd = fake()->dateTimeBetween(
            'today 13:00:00',
            'today 14:00:00'
        );

        return [
            'attendance_id' => Attendance::factory(),
            'rest_start' => $restStart,
            'rest_end' => $restEnd,
        ];
    }
}
