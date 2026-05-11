<?php

use App\Http\Controllers\PeriodClosingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('period-closings', [PeriodClosingController::class, 'index']);
    Route::get('period-closings/{period_closing}', [PeriodClosingController::class, 'show']);
    Route::post('period-closings', [PeriodClosingController::class, 'store']);
    Route::post('period-closings/{period_closing}/close', [PeriodClosingController::class, 'close']);
    Route::post('period-closings/{period_closing}/reopen', [PeriodClosingController::class, 'reopen']);
    Route::post('period-closings/export', [PeriodClosingController::class, 'export']);
});
