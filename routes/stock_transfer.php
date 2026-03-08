<?php

use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockTransferItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:stock_transfer,true')->group(function () {
        Route::get('stock-transfers', [StockTransferController::class, 'index']);
        Route::get('stock-transfers/{stockTransfer}', [StockTransferController::class, 'show']);
        Route::post('stock-transfers', [StockTransferController::class, 'store'])->middleware('permission:stock_transfer.create,true');
        Route::put('stock-transfers/{stockTransfer}', [StockTransferController::class, 'update'])->middleware('permission:stock_transfer.edit,true');
        Route::delete('stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])->middleware('permission:stock_transfer.delete,true');
        Route::post('stock-transfers/export', [StockTransferController::class, 'export']);

        Route::get('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'getItems']);
        Route::post('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'syncItems'])->middleware('permission:stock_transfer.edit,true');
    });
});
