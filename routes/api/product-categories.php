<?php

use App\Http\Controllers\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:product_category,true')->group(function () {
    Route::get('product-categories', [ProductCategoryController::class, 'index']);

    Route::get('product-categories/{product_category}', [ProductCategoryController::class, 'show']);

    Route::post('product-categories', [ProductCategoryController::class, 'store'])->middleware('permission:product_category.create,true');

    Route::put('product-categories/{product_category}', [ProductCategoryController::class, 'update'])->middleware('permission:product_category.edit,true');

    Route::delete('product-categories/{product_category}', [ProductCategoryController::class, 'destroy'])->middleware('permission:product_category.delete,true');
    Route::post('product-categories/export', [ProductCategoryController::class, 'export']);
    Route::post('product-categories/import', [ProductCategoryController::class, 'import'])->middleware('permission:product_category.create,true');
});
