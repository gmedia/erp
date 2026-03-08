<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:account,true')->group(function () {
        Route::get('accounts', [AccountController::class, 'index']);
        Route::get('accounts/{account}', [AccountController::class, 'show']);
        Route::post('accounts', [AccountController::class, 'store'])->middleware('permission:account.create,true');
        Route::put('accounts/{account}', [AccountController::class, 'update'])->middleware('permission:account.edit,true');
        Route::delete('accounts/{account}', [AccountController::class, 'destroy'])->middleware('permission:account.delete,true');
        Route::post('accounts/export', [AccountController::class, 'export']);
    });
});