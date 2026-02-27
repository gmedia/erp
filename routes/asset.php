<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('assets', function () {
        return Inertia::render('assets/index');
    })->name('assets')->middleware('permission:asset');

    Route::get('assets/{asset}', [AssetController::class, 'profile'])
        ->name('assets.profile')
        ->middleware('permission:asset_profile');
        
    Route::get('asset-dashboard', [AssetDashboardController::class, 'index'])
        ->name('asset-dashboard')
        ->middleware('permission:asset');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/asset-dashboard/data', [AssetDashboardController::class, 'getData'])
        ->middleware('permission:asset');

    Route::middleware('permission:asset,true')->group(function () {
        Route::get('assets', [AssetController::class, 'index'])->name('assets.index');
        Route::get('assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
        Route::post('assets', [AssetController::class, 'store'])->name('assets.store')->middleware('permission:asset.create,true');
        Route::put('assets/{asset}', [AssetController::class, 'update'])->name('assets.update')->middleware('permission:asset.edit,true');
        Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy')->middleware('permission:asset.delete,true');
        Route::post('assets/export', [AssetController::class, 'export'])->name('assets.export');
        Route::post('assets/import', [AssetController::class, 'import'])->name('assets.import')->middleware('permission:asset.create,true');
    });
});
