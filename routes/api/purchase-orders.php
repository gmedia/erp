<?php

use App\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::middleware('permission:purchase_order,true')->group(function () {
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
        Route::post('purchase-orders/export', [PurchaseOrderController::class, 'export']);

        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
            ->middleware('permission:purchase_order.create,true');
        Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])
            ->middleware('permission:purchase_order.edit,true');
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])
            ->middleware('permission:purchase_order.delete,true');
    });
});
