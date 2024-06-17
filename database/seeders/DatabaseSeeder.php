<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Driver;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Vehicle;
use App\Models\Stop;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        Route::factory()->count(20)->create();
        Stop::factory()->count(70)->create();
        Schedule::factory()->count(40)->create();
        Vehicle::factory()->count(20)->create();
        Driver::factory()->count(25)->create();
    }
}
