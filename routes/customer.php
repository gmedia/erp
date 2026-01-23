<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('customers', function () {
        return Inertia::render('customers/index');
    })->name('customers')->middleware('permission:customer');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:customer,true')->group(function () {
        Route::get('customers', [CustomerController::class, 'index']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
        Route::post('customers', [CustomerController::class, 'store'])->middleware('permission:customer.create,true');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customer.edit,true');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customer.delete,true');
    });
});
