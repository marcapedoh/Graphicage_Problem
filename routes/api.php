<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\StopController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\DriverController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::apiResource('routes', RouteController::class);
Route::apiResource('stops', StopController::class);
Route::apiResource('schedules', ScheduleController::class);
Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('drivers', DriverController::class);
Route::get('optimize-schedule', [ScheduleController::class, 'createAndOptimizeSchedule']);
Route::get('defineAndOptimizeRoutes', [RouteController::class, 'defineAndOptimizeRoutes']);