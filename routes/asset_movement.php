<?php

use App\Http\Controllers\AssetMovementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('asset-movements', function () {
        return Inertia::render('asset-movements/index');
    })->name('asset-movements')->middleware('permission:asset_movement');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_movement,true')->group(function () {
        Route::get('asset-movements', [AssetMovementController::class, 'index'])->name('asset-movements.index');
        Route::get('asset-movements/{asset_movement}', [AssetMovementController::class, 'show'])->name('asset-movements.show');
        Route::post('asset-movements', [AssetMovementController::class, 'store'])->name('asset-movements.store')->middleware('permission:asset_movement.create,true');
        Route::put('asset-movements/{asset_movement}', [AssetMovementController::class, 'update'])->name('asset-movements.update')->middleware('permission:asset_movement.edit,true');
        Route::delete('asset-movements/{asset_movement}', [AssetMovementController::class, 'destroy'])->name('asset-movements.destroy')->middleware('permission:asset_movement.delete,true');
        Route::post('asset-movements/export', [AssetMovementController::class, 'export'])->name('asset-movements.export');
    });
});
