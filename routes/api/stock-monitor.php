<?php

use App\Http\Controllers\StockMonitorController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:stock_monitor,true')->group(function () {
    Route::get('stock-monitor', [StockMonitorController::class, 'index']);
    Route::post('stock-monitor/export', [StockMonitorController::class, 'export']);
});
