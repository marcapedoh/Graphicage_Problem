<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Schedule;
use App\Models\Route;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Schedule::class;

    public function definition()
    {
        return [
            'route_id' => Route::factory(),
            'departure_time' => $this->faker->time,
            'arrival_time' => $this->faker->time,
        ];
    }
}
