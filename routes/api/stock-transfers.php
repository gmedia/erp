<?php

use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockTransferItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:stock_transfer,true')->group(function () {
    Route::get('stock-transfers', [StockTransferController::class, 'index']);

    Route::get('stock-transfers/{stock_transfer}', [StockTransferController::class, 'show']);

    Route::post('stock-transfers', [StockTransferController::class, 'store'])
        ->middleware('permission:stock_transfer.create,true');

    Route::put('stock-transfers/{stock_transfer}', [StockTransferController::class, 'update'])
        ->middleware('permission:stock_transfer.edit,true');

    Route::delete('stock-transfers/{stock_transfer}', [StockTransferController::class, 'destroy'])
        ->middleware('permission:stock_transfer.delete,true');
    Route::post('stock-transfers/export', [StockTransferController::class, 'export']);
    Route::post('stock-transfers/import', [StockTransferController::class, 'import'])
        ->middleware('permission:stock_transfer.create,true');
    Route::get('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'getItems']);
    Route::post('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'syncItems']);
});
