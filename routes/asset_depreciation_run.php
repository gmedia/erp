<?php

use App\Http\Controllers\AssetDepreciationRunController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('asset-depreciation-runs', [AssetDepreciationRunController::class, 'index'])
        ->name('asset-depreciation-runs.index')
        ->middleware('permission:asset_depreciation_run');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_depreciation_run,true')->group(function () {
        Route::get('asset-depreciation-runs', [AssetDepreciationRunController::class, 'apiIndex']);
        Route::post('asset-depreciation-runs/calculate', [AssetDepreciationRunController::class, 'calculate']);
        Route::get('asset-depreciation-runs/{assetDepreciationRun}/lines', [AssetDepreciationRunController::class, 'lines']);
        Route::post('asset-depreciation-runs/{assetDepreciationRun}/post', [AssetDepreciationRunController::class, 'postToJournal']);
    });
});
