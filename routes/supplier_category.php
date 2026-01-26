<?php

use App\Http\Controllers\SupplierCategoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('supplier-categories', function () {
        return Inertia::render('supplier-categories/index');
    })->name('supplier-categories')->middleware('permission:supplier_category');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:supplier_category,true')->group(function () {
        Route::get('supplier-categories', [SupplierCategoryController::class, 'index']);
        Route::get('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'show']);
        Route::post('supplier-categories', [SupplierCategoryController::class, 'store'])->middleware('permission:supplier_category.create,true');
        Route::put('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'update'])->middleware('permission:supplier_category.edit,true');
        Route::delete('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'destroy'])->middleware('permission:supplier_category.delete,true');
        Route::post('supplier-categories/export', [SupplierCategoryController::class, 'export']);
    });
});
