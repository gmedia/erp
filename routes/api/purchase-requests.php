<?php

use App\Http\Controllers\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:purchase_request,true')->group(function () {
    Route::get('purchase-requests', [PurchaseRequestController::class, 'index']);
    Route::get('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
    Route::post('purchase-requests', [PurchaseRequestController::class, 'store']);
    Route::put('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'update']);
    Route::delete('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'destroy']);
    Route::post('purchase-requests/export', [PurchaseRequestController::class, 'export']);
});
