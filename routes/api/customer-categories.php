<?php

use App\Http\Controllers\CustomerCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:customer_category,true')->group(function () {
    Route::get('customer-categories', [CustomerCategoryController::class, 'index']);

    Route::get('customer-categories/{customer_category}', [CustomerCategoryController::class, 'show']);

    Route::post('customer-categories', [CustomerCategoryController::class, 'store'])->middleware('permission:customer_category.create,true');

    Route::put('customer-categories/{customer_category}', [CustomerCategoryController::class, 'update'])->middleware('permission:customer_category.edit,true');

    Route::delete('customer-categories/{customer_category}', [CustomerCategoryController::class, 'destroy'])->middleware('permission:customer_category.delete,true');
    Route::post('customer-categories/export', [CustomerCategoryController::class, 'export']);
    Route::post('customer-categories/import', [CustomerCategoryController::class, 'import']);
});
