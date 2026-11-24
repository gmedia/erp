<?php

use App\Http\Controllers\PurchaseRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:purchase_request,true')->group(function () {
    Route::get('purchase-requests', [PurchaseRequestController::class, 'index']);
    Route::get('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
    Route::post('purchase-requests/export', [PurchaseRequestController::class, 'export']);

    Route::post('purchase-requests', [PurchaseRequestController::class, 'store'])
        ->middleware('permission:purchase_request.create,true');
    Route::put('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'update'])
        ->middleware('permission:purchase_request.edit,true');
    Route::delete('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])
        ->middleware('permission:purchase_request.delete,true');
});
