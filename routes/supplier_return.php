<?php

use App\Http\Controllers\SupplierReturnController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('supplier-returns', function () {
        return Inertia::render('supplier-returns/index');
    })->name('supplier-returns')->middleware('permission:supplier_return');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:supplier_return,true')->group(function () {
        Route::get('supplier-returns', [SupplierReturnController::class, 'index']);
        Route::get('supplier-returns/{supplierReturn}', [SupplierReturnController::class, 'show']);
        Route::post('supplier-returns', [SupplierReturnController::class, 'store'])->middleware('permission:supplier_return.create,true');
        Route::put('supplier-returns/{supplierReturn}', [SupplierReturnController::class, 'update'])->middleware('permission:supplier_return.edit,true');
        Route::delete('supplier-returns/{supplierReturn}', [SupplierReturnController::class, 'destroy'])->middleware('permission:supplier_return.delete,true');
        Route::post('supplier-returns/export', [SupplierReturnController::class, 'export']);
    });
});
