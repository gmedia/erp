<?php

use App\Http\Controllers\PurchaseRequestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('purchase-requests', function () {
        return Inertia::render('purchase-requests/index');
    })->name('purchase-requests')->middleware('permission:purchase_request');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:purchase_request,true')->group(function () {
        Route::get('purchase-requests', [PurchaseRequestController::class, 'index']);
        Route::get('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
        Route::post('purchase-requests', [PurchaseRequestController::class, 'store']);
        Route::put('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'update']);
        Route::delete('purchase-requests/{purchaseRequest}', [PurchaseRequestController::class, 'destroy']);
        Route::post('purchase-requests/export', [PurchaseRequestController::class, 'export']);
    });
});
