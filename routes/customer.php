<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:customer,true')->group(function () {
        Route::get('customers', [CustomerController::class, 'index']);
        Route::post('customers/export', [CustomerController::class, 'export']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
        Route::post('customers', [CustomerController::class, 'store'])->middleware('permission:customer.create,true');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customer.edit,true');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customer.delete,true');
    });
});
