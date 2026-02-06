<?php

use App\Http\Controllers\AccountMappingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('account-mappings', function () {
        return Inertia::render('account-mappings/index');
    })->name('account-mappings')->middleware('permission:account_mapping');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:account_mapping,true')->group(function () {
        Route::get('account-mappings', [AccountMappingController::class, 'index']);
        Route::get('account-mappings/{accountMapping}', [AccountMappingController::class, 'show']);
        Route::post('account-mappings', [AccountMappingController::class, 'store'])->middleware('permission:account_mapping.create,true');
        Route::put('account-mappings/{accountMapping}', [AccountMappingController::class, 'update'])->middleware('permission:account_mapping.edit,true');
        Route::delete('account-mappings/{accountMapping}', [AccountMappingController::class, 'destroy'])->middleware('permission:account_mapping.delete,true');
        Route::post('account-mappings/export', [AccountMappingController::class, 'export']);
    });
});
