<?php

use App\Http\Controllers\ArReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:ar_receipt'])->group(function () {
    Route::get('ar-receipts', [ArReceiptController::class, 'index']);
    Route::get('ar-receipts/{ar_receipt}', [ArReceiptController::class, 'show']);
    Route::post('ar-receipts/export', [ArReceiptController::class, 'export']);

    Route::post('ar-receipts', [ArReceiptController::class, 'store'])
        ->middleware('permission:ar_receipt.create,true');
    Route::put('ar-receipts/{ar_receipt}', [ArReceiptController::class, 'update'])
        ->middleware('permission:ar_receipt.edit,true');
    Route::delete('ar-receipts/{ar_receipt}', [ArReceiptController::class, 'destroy'])
        ->middleware('permission:ar_receipt.delete,true');
});
