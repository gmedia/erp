<?php

use App\Http\Controllers\PeriodClosingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:period_closing,true'])->group(function () {
    Route::get('period-closings', [PeriodClosingController::class, 'index']);
    Route::get('period-closings/{period_closing}', [PeriodClosingController::class, 'show']);

    Route::post('period-closings', [PeriodClosingController::class, 'store'])
        ->middleware('permission:period_closing.create,true');

    Route::post('period-closings/{period_closing}/close', [PeriodClosingController::class, 'close'])
        ->middleware('permission:period_closing.edit,true');
    Route::post('period-closings/{period_closing}/reopen', [PeriodClosingController::class, 'reopen'])
        ->middleware('permission:period_closing.edit,true');
    Route::post('period-closings/export', [PeriodClosingController::class, 'export']);
});
