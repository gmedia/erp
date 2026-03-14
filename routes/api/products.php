<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:product,true')->group(function () {
    Route::get('products', [ProductController::class, 'index']);

    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::post('products', [ProductController::class, 'store'])
        ->middleware('permission:product.create,true');

    Route::put('products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:product.edit,true');

    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:product.delete,true');
    Route::post('products/export', [ProductController::class, 'export']);
    Route::post('products/import', [ProductController::class, 'import'])
        ->middleware('permission:product.create,true');
});
