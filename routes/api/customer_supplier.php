<?php

use App\Http\Controllers\CustomerCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierCategoryController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// --- Customer & Supplier ---
Route::middleware('permission:customer,true')->group(function () {
    Route::get('customers', [CustomerController::class, 'index']);

    Route::get('customers/{customer}', [CustomerController::class, 'show']);

    Route::post('customers', [CustomerController::class, 'store'])->middleware('permission:customer.create,true');

    Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customer.edit,true');

    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customer.delete,true');
    Route::post('customers/export', [CustomerController::class, 'export']);
    Route::post('customers/import', [CustomerController::class, 'import']);
});
Route::middleware('permission:customer_category,true')->group(function () {
    Route::get('customer-categories', [CustomerCategoryController::class, 'index']);

    Route::get('customer-categories/{customer_category}', [CustomerCategoryController::class, 'show']);

    Route::post('customer-categories', [CustomerCategoryController::class, 'store'])->middleware('permission:customer_category.create,true');

    Route::put('customer-categories/{customer_category}', [CustomerCategoryController::class, 'update'])->middleware('permission:customer_category.edit,true');

    Route::delete('customer-categories/{customer_category}', [CustomerCategoryController::class, 'destroy'])->middleware('permission:customer_category.delete,true');
    Route::post('customer-categories/export', [CustomerCategoryController::class, 'export']);
    Route::post('customer-categories/import', [CustomerCategoryController::class, 'import']);
});
Route::middleware('permission:supplier,true')->group(function () {
    Route::get('suppliers', [SupplierController::class, 'index']);

    Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);

    Route::post('suppliers', [SupplierController::class, 'store'])->middleware('permission:supplier.create,true');

    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:supplier.edit,true');

    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:supplier.delete,true');
    Route::post('suppliers/export', [SupplierController::class, 'export']);
    Route::post('suppliers/import', [SupplierController::class, 'import']);
});
Route::middleware('permission:supplier_category,true')->group(function () {
    Route::get('supplier-categories', [SupplierCategoryController::class, 'index']);

    Route::get('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'show']);

    Route::post('supplier-categories', [SupplierCategoryController::class, 'store'])->middleware('permission:supplier_category.create,true');

    Route::put('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'update'])->middleware('permission:supplier_category.edit,true');

    Route::delete('supplier-categories/{supplier_category}', [SupplierCategoryController::class, 'destroy'])->middleware('permission:supplier_category.delete,true');
    Route::post('supplier-categories/export', [SupplierCategoryController::class, 'export']);
    Route::post('supplier-categories/import', [SupplierCategoryController::class, 'import']);
});
