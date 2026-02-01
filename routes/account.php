<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    // Web Routes
    Route::get('accounts', function () {
        return Inertia::render('accounts/index');
    })->name('accounts.index');

    // API Routes
    Route::prefix('api')->group(function () {
        Route::get('accounts', [AccountController::class, 'index'])->name('api.accounts.index');
        Route::post('accounts', [AccountController::class, 'store'])->name('api.accounts.store');
        Route::get('accounts/{account}', [AccountController::class, 'show'])->name('api.accounts.show');
        Route::put('accounts/{account}', [AccountController::class, 'update'])->name('api.accounts.update');
        Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->name('api.accounts.destroy');
        Route::post('accounts/export', [AccountController::class, 'export'])->name('api.accounts.export');
    });
});
