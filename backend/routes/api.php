<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Finance\CashFlowController;
use App\Http\Controllers\Api\V1\Finance\GenerateMonthlyPaymentsController;
use App\Http\Controllers\Api\V1\Finance\IndexFinancialTransactionController;
use App\Http\Controllers\Api\V1\Finance\StoreFinancialTransactionController;
use App\Http\Controllers\Api\V1\Finance\UpdateFinancialTransactionController;
use App\Http\Controllers\Api\V1\Finance\UpdateFinancialTransactionStatusController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Players\IndexPlayerController;
use App\Http\Controllers\Api\V1\Players\ShowPlayerController;
use App\Http\Controllers\Api\V1\Players\StorePlayerController;
use App\Http\Controllers\Api\V1\Players\UpdatePlayerController;
use App\Http\Controllers\Api\V1\Players\UpdatePlayerStatusController;
use App\Http\Controllers\Api\V1\Positions\IndexPositionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('health', HealthController::class);

    Route::post('auth/login', LoginController::class);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', LogoutController::class);
        Route::get('auth/me', MeController::class);

        Route::get('positions', IndexPositionController::class);

        Route::get('players', IndexPlayerController::class);
        Route::post('players', StorePlayerController::class);
        Route::get('players/{player}', ShowPlayerController::class);
        Route::put('players/{player}', UpdatePlayerController::class);
        Route::patch('players/{player}/status', UpdatePlayerStatusController::class);

        Route::post('financial-transactions/monthly-payments', GenerateMonthlyPaymentsController::class);
        Route::get('financial-transactions/cash-flow', CashFlowController::class);
        Route::get('financial-transactions', IndexFinancialTransactionController::class);
        Route::post('financial-transactions', StoreFinancialTransactionController::class);
        Route::put('financial-transactions/{financialTransaction}', UpdateFinancialTransactionController::class);
        Route::patch('financial-transactions/{financialTransaction}/status', UpdateFinancialTransactionStatusController::class);
    });
});
