<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class);

    Route::post('auth/login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', LogoutController::class);
        Route::get('auth/me', MeController::class);
    });
});
