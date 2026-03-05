<?php

use App\Http\Controllers\StockMonitorController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('stock-monitor', [StockMonitorController::class, 'index'])
        ->name('stock-monitor')
        ->middleware('permission:stock_monitor');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('stock-monitor', [StockMonitorController::class, 'index'])
        ->middleware('permission:stock_monitor');

    Route::post('stock-monitor/export', [StockMonitorController::class, 'export'])
        ->middleware('permission:stock_monitor');
});
