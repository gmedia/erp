<?php

use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:stock_movement,true')->group(function () {
    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::post('stock-movements/export', [StockMovementController::class, 'export']);
});
