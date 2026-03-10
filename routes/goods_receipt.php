<?php

use App\Http\Controllers\GoodsReceiptController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('goods-receipts', function () {
        return Inertia::render('goods-receipts/index');
    })->name('goods-receipts')->middleware('permission:goods_receipt');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:goods_receipt,true')->group(function () {
        Route::get('goods-receipts', [GoodsReceiptController::class, 'index']);
        Route::get('goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'show']);
        Route::post('goods-receipts', [GoodsReceiptController::class, 'store'])->middleware('permission:goods_receipt.create,true');
        Route::put('goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'update'])->middleware('permission:goods_receipt.edit,true');
        Route::delete('goods-receipts/{goodsReceipt}', [GoodsReceiptController::class, 'destroy'])->middleware('permission:goods_receipt.delete,true');
        Route::post('goods-receipts/export', [GoodsReceiptController::class, 'export']);
    });
});
