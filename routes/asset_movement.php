<?php

use App\Http\Controllers\AssetMovementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('asset-movements', [AssetMovementController::class, 'index'])
        ->name('asset-movements.index')
        ->middleware('permission:asset_movement');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_movement,true')->group(function () {
        Route::get('asset-movements', [AssetMovementController::class, 'index'])->name('api.asset-movements.index');
        Route::get('asset-movements/{asset_movement}', [AssetMovementController::class, 'show'])->name('api.asset-movements.show');
        Route::post('asset-movements', [AssetMovementController::class, 'store'])->name('api.asset-movements.store');
        Route::put('asset-movements/{asset_movement}', [AssetMovementController::class, 'update'])->name('api.asset-movements.update');
        Route::delete('asset-movements/{asset_movement}', [AssetMovementController::class, 'destroy'])->name('api.asset-movements.destroy');
        Route::post('asset-movements/export', [AssetMovementController::class, 'export'])->name('api.asset-movements.export');
    });
});
