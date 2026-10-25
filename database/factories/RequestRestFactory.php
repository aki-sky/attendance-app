<?php

namespace Database\Factories;

use App\Models\RequestAttendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RequestRest>
 */
class RequestRestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $requestRestStart = fake()->dateTimeBetween(
            'today 12:00:00',
            'today 13:00:00'
        );

        $requestRestEnd = fake()->dateTimeBetween(
            'today 13:00:00',
            'today 14:00:00'
        );

        return [
            'request_attendance_id' => RequestAttendance::factory(),
            'request_rest_start' => $requestRestStart,
            'request_rest_end' => $requestRestEnd,
        ];
    }
}
