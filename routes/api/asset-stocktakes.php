<?php

use App\Http\Controllers\AssetStocktakeController;
use App\Http\Controllers\AssetStocktakeItemController;
use App\Http\Controllers\AssetStocktakeVarianceController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:asset_stocktake,true')->group(function () {
    Route::get('asset-stocktakes', [AssetStocktakeController::class, 'index']);

    Route::get('asset-stocktakes/{asset_stocktake}', [AssetStocktakeController::class, 'show']);

    Route::post('asset-stocktakes', [AssetStocktakeController::class, 'store'])->middleware('permission:asset_stocktake.create,true');

    Route::put('asset-stocktakes/{asset_stocktake}', [AssetStocktakeController::class, 'update'])->middleware('permission:asset_stocktake.edit,true');

    Route::delete('asset-stocktakes/{asset_stocktake}', [AssetStocktakeController::class, 'destroy'])->middleware('permission:asset_stocktake.delete,true');
    Route::post('asset-stocktakes/export', [AssetStocktakeController::class, 'export']);
    Route::post('asset-stocktakes/import', [AssetStocktakeController::class, 'import'])->middleware('permission:asset_stocktake.create,true');
    Route::get('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'getItems']);
    Route::post('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'syncItems']);
    Route::get('asset-stocktake-variances', [AssetStocktakeVarianceController::class, 'index']);
    Route::post('asset-stocktake-variances/export', [AssetStocktakeVarianceController::class, 'export']);
});
