<?php

use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockAdjustmentItemController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('stock-adjustments', function () {
        return Inertia::render('stock-adjustments/index');
    })->name('stock-adjustments')->middleware('permission:stock_adjustment');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:stock_adjustment,true')->group(function () {
        Route::get('stock-adjustments', [StockAdjustmentController::class, 'index']);
        Route::get('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show']);
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->middleware('permission:stock_adjustment.create,true');
        Route::put('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'update'])->middleware('permission:stock_adjustment.edit,true');
        Route::delete('stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'destroy'])->middleware('permission:stock_adjustment.delete,true');
        Route::post('stock-adjustments/export', [StockAdjustmentController::class, 'export']);

        Route::get('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'getItems']);
        Route::post('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'syncItems'])->middleware('permission:stock_adjustment.edit,true');
    });
});
