<?php

use App\Http\Controllers\AssetStocktakeController;
use App\Http\Controllers\AssetStocktakeItemController;
use App\Http\Controllers\AssetStocktakeVarianceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_stocktake,true')->group(function () {
        Route::get('asset-stocktakes', [AssetStocktakeController::class, 'index']);
        Route::get('asset-stocktakes/{assetStocktake}', [AssetStocktakeController::class, 'show']);
        Route::post('asset-stocktakes', [AssetStocktakeController::class, 'store'])->middleware('permission:asset_stocktake.create,true');
        Route::put('asset-stocktakes/{assetStocktake}', [AssetStocktakeController::class, 'update'])->middleware('permission:asset_stocktake.edit,true');
        Route::delete('asset-stocktakes/{assetStocktake}', [AssetStocktakeController::class, 'destroy'])->middleware('permission:asset_stocktake.delete,true');
        Route::post('asset-stocktakes/export', [AssetStocktakeController::class, 'export']);

        Route::get('asset-stocktake-variances', [AssetStocktakeVarianceController::class, 'index']);
        Route::post('asset-stocktake-variances/export', [AssetStocktakeVarianceController::class, 'export']);

        Route::get('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'getItems']);
        Route::post('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'syncItems'])->middleware('permission:asset_stocktake.edit,true');
    });
});
