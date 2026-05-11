<?php

use App\Http\Controllers\ArReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:ar_receipt'])->group(function () {
    Route::get('ar-receipts', [ArReceiptController::class, 'index']);
    Route::post('ar-receipts', [ArReceiptController::class, 'store']);
    Route::get('ar-receipts/{ar_receipt}', [ArReceiptController::class, 'show']);
    Route::put('ar-receipts/{ar_receipt}', [ArReceiptController::class, 'update']);
    Route::delete('ar-receipts/{ar_receipt}', [ArReceiptController::class, 'destroy']);
    Route::post('ar-receipts/export', [ArReceiptController::class, 'export']);
});
