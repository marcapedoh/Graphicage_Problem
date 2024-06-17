<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Route;
use App\Models\Stop;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stop>
 */
class StopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Stop::class;

    public function definition()
    {
        return [
            'name' => $this->faker->streetName,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
            'route_id' => Route::factory(),
        ];
    }
}
