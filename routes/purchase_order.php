<?php

use App\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('purchase-orders', function () {
        return Inertia::render('purchase-orders/index');
    })->name('purchase-orders')->middleware('permission:purchase_order');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:purchase_order,true')->group(function () {
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);
        Route::post('purchase-orders/export', [PurchaseOrderController::class, 'export']);
    });
});
