<?php

use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDashboardController;
use App\Http\Controllers\AssetDepreciationRunController;
use App\Http\Controllers\AssetLocationController;
use App\Http\Controllers\AssetMaintenanceController;
use App\Http\Controllers\AssetModelController;
use App\Http\Controllers\AssetMovementController;
use App\Http\Controllers\AssetStocktakeController;
use App\Http\Controllers\AssetStocktakeItemController;
use App\Http\Controllers\AssetStocktakeVarianceController;
use Illuminate\Support\Facades\Route;

// --- Assets ---
Route::middleware('permission:asset,true')->group(function () {
    Route::apiResource('assets', AssetController::class);
    Route::get('assets/{asset}/profile', [AssetController::class, 'profile']);
    Route::post('assets/export', [AssetController::class, 'export']);
    Route::post('assets/import', [AssetController::class, 'import']);
    Route::get('asset-dashboard/data', [AssetDashboardController::class, 'getData']);
});
Route::middleware('permission:asset_category,true')->group(function () {
    Route::apiResource('asset-categories', AssetCategoryController::class);
    Route::post('asset-categories/export', [AssetCategoryController::class, 'export']);
    Route::post('asset-categories/import', [AssetCategoryController::class, 'import']);
});
Route::middleware('permission:asset_model,true')->group(function () {
    Route::apiResource('asset-models', AssetModelController::class);
    Route::post('asset-models/export', [AssetModelController::class, 'export']);
    Route::post('asset-models/import', [AssetModelController::class, 'import']);
});
Route::middleware('permission:asset_location,true')->group(function () {
    Route::apiResource('asset-locations', AssetLocationController::class);
    Route::post('asset-locations/export', [AssetLocationController::class, 'export']);
    Route::post('asset-locations/import', [AssetLocationController::class, 'import']);
});
Route::middleware('permission:asset_movement,true')->group(function () {
    Route::apiResource('asset-movements', AssetMovementController::class);
    Route::post('asset-movements/export', [AssetMovementController::class, 'export']);
    Route::post('asset-movements/import', [AssetMovementController::class, 'import']);
});
Route::middleware('permission:asset_maintenance,true')->group(function () {
    Route::apiResource('asset-maintenances', AssetMaintenanceController::class);
    Route::post('asset-maintenances/export', [AssetMaintenanceController::class, 'export']);
    Route::post('asset-maintenances/import', [AssetMaintenanceController::class, 'import']);
});

// Asset Stocktake (with nested items + variances)
Route::middleware('permission:asset_stocktake,true')->group(function () {
    Route::apiResource('asset-stocktakes', AssetStocktakeController::class);
    Route::post('asset-stocktakes/export', [AssetStocktakeController::class, 'export']);
    Route::post('asset-stocktakes/import', [AssetStocktakeController::class, 'import']);
    Route::get('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'getItems']);
    Route::post('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'syncItems']);
    Route::get('asset-stocktake-variances', [AssetStocktakeVarianceController::class, 'index']);
    Route::post('asset-stocktake-variances/export', [AssetStocktakeVarianceController::class, 'export']);
});

// Asset Depreciation
Route::middleware('permission:asset_depreciation_run,true')->group(function () {
    Route::get('asset-depreciation-runs', [AssetDepreciationRunController::class, 'apiIndex']);
    Route::post('asset-depreciation-runs/calculate', [AssetDepreciationRunController::class, 'calculate']);
    Route::get('asset-depreciation-runs/{assetDepreciationRun}/lines', [AssetDepreciationRunController::class, 'lines']);
    Route::post('asset-depreciation-runs/{assetDepreciationRun}/post', [AssetDepreciationRunController::class, 'postToJournal']);
});
