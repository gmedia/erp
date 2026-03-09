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
Route::middleware('permission:asset_category,true')->group(function () {
    Route::get('asset-categories', [AssetCategoryController::class, 'index']);

    Route::get('asset-categories/{asset_category}', [AssetCategoryController::class, 'show']);

    Route::post('asset-categories', [AssetCategoryController::class, 'store'])->middleware('permission:asset_category.create,true');

    Route::put('asset-categories/{asset_category}', [AssetCategoryController::class, 'update'])->middleware('permission:asset_category.edit,true');

    Route::delete('asset-categories/{asset_category}', [AssetCategoryController::class, 'destroy'])->middleware('permission:asset_category.delete,true');
    Route::post('asset-categories/export', [AssetCategoryController::class, 'export']);
    Route::post('asset-categories/import', [AssetCategoryController::class, 'import']);
});
Route::middleware('permission:asset_model,true')->group(function () {
    Route::get('asset-models', [AssetModelController::class, 'index']);

    Route::get('asset-models/{asset_model}', [AssetModelController::class, 'show']);

    Route::post('asset-models', [AssetModelController::class, 'store'])->middleware('permission:asset_model.create,true');

    Route::put('asset-models/{asset_model}', [AssetModelController::class, 'update'])->middleware('permission:asset_model.edit,true');

    Route::delete('asset-models/{asset_model}', [AssetModelController::class, 'destroy'])->middleware('permission:asset_model.delete,true');
    Route::post('asset-models/export', [AssetModelController::class, 'export']);
    Route::post('asset-models/import', [AssetModelController::class, 'import']);
});
Route::middleware('permission:asset_location,true')->group(function () {
    Route::get('asset-locations', [AssetLocationController::class, 'index']);

    Route::get('asset-locations/{asset_location}', [AssetLocationController::class, 'show']);

    Route::post('asset-locations', [AssetLocationController::class, 'store'])->middleware('permission:asset_location.create,true');

    Route::put('asset-locations/{asset_location}', [AssetLocationController::class, 'update'])->middleware('permission:asset_location.edit,true');

    Route::delete('asset-locations/{asset_location}', [AssetLocationController::class, 'destroy'])->middleware('permission:asset_location.delete,true');
    Route::post('asset-locations/export', [AssetLocationController::class, 'export']);
    Route::post('asset-locations/import', [AssetLocationController::class, 'import']);
});
Route::middleware('permission:asset_movement,true')->group(function () {
    Route::get('asset-movements', [AssetMovementController::class, 'index']);

    Route::get('asset-movements/{asset_movement}', [AssetMovementController::class, 'show']);

    Route::post('asset-movements', [AssetMovementController::class, 'store'])->middleware('permission:asset_movement.create,true');

    Route::put('asset-movements/{asset_movement}', [AssetMovementController::class, 'update'])->middleware('permission:asset_movement.edit,true');

    Route::delete('asset-movements/{asset_movement}', [AssetMovementController::class, 'destroy'])->middleware('permission:asset_movement.delete,true');
    Route::post('asset-movements/export', [AssetMovementController::class, 'export']);
    Route::post('asset-movements/import', [AssetMovementController::class, 'import']);
});
Route::middleware('permission:asset_maintenance,true')->group(function () {
    Route::get('asset-maintenances', [AssetMaintenanceController::class, 'index']);

    Route::get('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'show']);

    Route::post('asset-maintenances', [AssetMaintenanceController::class, 'store'])->middleware('permission:asset_maintenance.create,true');

    Route::put('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'update'])->middleware('permission:asset_maintenance.edit,true');

    Route::delete('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'destroy'])->middleware('permission:asset_maintenance.delete,true');
    Route::post('asset-maintenances/export', [AssetMaintenanceController::class, 'export']);
    Route::post('asset-maintenances/import', [AssetMaintenanceController::class, 'import']);
});

// Asset Stocktake (with nested items + variances)
Route::middleware('permission:asset_stocktake,true')->group(function () {
    Route::get('asset-stocktakes', [AssetStocktakeController::class, 'index']);

    Route::get('asset-stocktakes/{asset_stocktake}', [AssetStocktakeController::class, 'show']);

    Route::post('asset-stocktakes', [AssetStocktakeController::class, 'store'])->middleware('permission:asset_stocktake.create,true');

    Route::put('asset-stocktakes/{asset_stocktake}', [AssetStocktakeController::class, 'update'])->middleware('permission:asset_stocktake.edit,true');

    Route::delete('asset-stocktakes/{asset_stocktake}', [AssetStocktakeController::class, 'destroy'])->middleware('permission:asset_stocktake.delete,true');
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
