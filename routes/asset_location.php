<?php

use App\Http\Controllers\AssetLocationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('asset-locations', function () {
        return Inertia::render('asset-locations/index');
    })->name('asset-locations')->middleware('permission:asset_location');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_location,true')->group(function () {
        Route::get('asset-locations', [AssetLocationController::class, 'index']);
        Route::get('asset-locations/{assetLocation}', [AssetLocationController::class, 'show']);
        Route::post('asset-locations', [AssetLocationController::class, 'store'])->middleware('permission:asset_location.create,true');
        Route::put('asset-locations/{assetLocation}', [AssetLocationController::class, 'update'])->middleware('permission:asset_location.edit,true');
        Route::delete('asset-locations/{assetLocation}', [AssetLocationController::class, 'destroy'])->middleware('permission:asset_location.delete,true');
        Route::post('asset-locations/export', [AssetLocationController::class, 'export']);
    });
});
