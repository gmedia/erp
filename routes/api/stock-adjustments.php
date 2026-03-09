<?php

use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockAdjustmentItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:stock_adjustment,true')->group(function () {
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index']);

    Route::get('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'show']);

    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->middleware('permission:stock_adjustment.create,true');

    Route::put('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'update'])->middleware('permission:stock_adjustment.edit,true');

    Route::delete('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'destroy'])->middleware('permission:stock_adjustment.delete,true');
    Route::post('stock-adjustments/export', [StockAdjustmentController::class, 'export']);
    Route::post('stock-adjustments/import', [StockAdjustmentController::class, 'import']);
    Route::get('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'getItems']);
    Route::post('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'syncItems']);
});
