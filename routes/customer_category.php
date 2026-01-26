<?php

use App\Http\Controllers\CustomerCategoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('customer-categories', function () {
        return Inertia::render('customer-categories/index');
    })->name('customer-categories')->middleware('permission:customer_category');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:customer_category,true')->group(function () {
        Route::get('customer-categories', [CustomerCategoryController::class, 'index']);
        Route::get('customer-categories/{customer_category}', [CustomerCategoryController::class, 'show']);
        Route::post('customer-categories', [CustomerCategoryController::class, 'store'])->middleware('permission:customer_category.create,true');
        Route::put('customer-categories/{customer_category}', [CustomerCategoryController::class, 'update'])->middleware('permission:customer_category.edit,true');
        Route::delete('customer-categories/{customer_category}', [CustomerCategoryController::class, 'destroy'])->middleware('permission:customer_category.delete,true');
        Route::post('customer-categories/export', [CustomerCategoryController::class, 'export']);
    });
});
