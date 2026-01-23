<?php

use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('suppliers', function () {
        return Inertia::render('suppliers/index');
    })->name('suppliers');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit', 'show']);
});
