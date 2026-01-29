<?php

use App\Http\Controllers\ProductCategoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Frontend route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('product-categories', function () {
        return Inertia::render('product-categories/index');
    })->name('product-categories')->middleware('permission:product_category');
});

// API routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:product_category,true')->group(function () {
        Route::get('product-categories', [ProductCategoryController::class, 'index']);
        Route::get('product-categories/{productCategory}', [ProductCategoryController::class, 'show']);
        Route::post('product-categories', [ProductCategoryController::class, 'store'])
            ->middleware('permission:product_category.create,true');
        Route::put('product-categories/{productCategory}', [ProductCategoryController::class, 'update'])
            ->middleware('permission:product_category.edit,true');
        Route::delete('product-categories/{productCategory}', [ProductCategoryController::class, 'destroy'])
            ->middleware('permission:product_category.delete,true');
        Route::post('product-categories/export', [ProductCategoryController::class, 'export']);
    });
});
