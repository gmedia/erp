<?php

use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('stock-movements', [StockMovementController::class, 'index'])
        ->name('stock-movements')
        ->middleware('permission:stock_movement');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('stock-movements', [StockMovementController::class, 'index'])
        ->middleware('permission:stock_movement');

    Route::post('stock-movements/export', [StockMovementController::class, 'export'])
        ->middleware('permission:stock_movement');
});

