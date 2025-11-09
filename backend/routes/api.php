<?php

use App\Http\Controllers\API\CommitsController;
use App\Http\Controllers\API\StatsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/stats/current-week', [StatsController::class, 'currentWeek']);
    Route::post('/sync', [StatsController::class, 'sync']);
});

// Worker endpoints (protected by API key)
Route::post('/commits/sync', [CommitsController::class, 'sync']);
Route::get('/worker/users', [\App\Http\Controllers\API\WorkerController::class, 'getUsers']);

// Public profile API
Route::get('/public/{username}', [\App\Http\Controllers\API\PublicProfileController::class, 'show']);

