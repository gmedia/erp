<?php

use App\Http\Controllers\CustomerCategoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('customer-categories')->name('customer-categories.')->group(function () {
    Route::post('/export', [CustomerCategoryController::class, 'export'])->name('export');
});

Route::resource('customer-categories', CustomerCategoryController::class);
