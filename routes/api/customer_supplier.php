<?php

use App\Http\Controllers\CustomerCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierCategoryController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// --- Customer & Supplier ---
Route::middleware('permission:customer,true')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/export', [CustomerController::class, 'export']);
    Route::post('customers/import', [CustomerController::class, 'import']);
});
Route::middleware('permission:customer_category,true')->group(function () {
    Route::apiResource('customer-categories', CustomerCategoryController::class);
    Route::post('customer-categories/export', [CustomerCategoryController::class, 'export']);
    Route::post('customer-categories/import', [CustomerCategoryController::class, 'import']);
});
Route::middleware('permission:supplier,true')->group(function () {
    Route::apiResource('suppliers', SupplierController::class);
    Route::post('suppliers/export', [SupplierController::class, 'export']);
    Route::post('suppliers/import', [SupplierController::class, 'import']);
});
Route::middleware('permission:supplier_category,true')->group(function () {
    Route::apiResource('supplier-categories', SupplierCategoryController::class);
    Route::post('supplier-categories/export', [SupplierCategoryController::class, 'export']);
    Route::post('supplier-categories/import', [SupplierCategoryController::class, 'import']);
});
