<?php

use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('suppliers', function () {
        return Inertia::render('suppliers/index');
    })->name('suppliers')->middleware('permission:supplier');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:supplier,true')->group(function () {
        Route::get('suppliers', [SupplierController::class, 'index']);
        Route::post('suppliers/export', [SupplierController::class, 'export']);
        Route::post('suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import')->middleware('permission:supplier.create,true');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);
        Route::post('suppliers', [SupplierController::class, 'store'])->middleware('permission:supplier.create,true');
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:supplier.edit,true');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:supplier.delete,true');
    });
});
