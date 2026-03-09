<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:asset,true')->group(function () {
    Route::get('assets', [AssetController::class, 'index']);

    Route::get('assets/{asset}', [AssetController::class, 'show']);

    Route::post('assets', [AssetController::class, 'store'])->middleware('permission:asset.create,true');

    Route::put('assets/{asset}', [AssetController::class, 'update'])->middleware('permission:asset.edit,true');

    Route::delete('assets/{asset}', [AssetController::class, 'destroy'])->middleware('permission:asset.delete,true');
    Route::get('assets/{asset}/profile', [AssetController::class, 'profile']);
    Route::post('assets/export', [AssetController::class, 'export']);
    Route::post('assets/import', [AssetController::class, 'import']);
    Route::get('asset-dashboard/data', [AssetDashboardController::class, 'getData']);
});
