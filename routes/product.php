<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('products', function () {
        return Inertia::render('products/index');
    })->name('products')->middleware('permission:product');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:product,true')->group(function () {
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
        Route::post('products', [ProductController::class, 'store'])->middleware('permission:product.create,true');
        Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:product.edit,true');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:product.delete,true');
        Route::post('products/export', [ProductController::class, 'export']);
    });
});
