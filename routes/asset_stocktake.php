<?php

use App\Http\Controllers\AssetStocktakeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('asset-stocktakes', function () {
        return Inertia::render('asset-stocktakes/index');
    })->name('asset-stocktakes')->middleware('permission:asset_stocktake');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_stocktake,true')->group(function () {
        Route::get('asset-stocktakes', [AssetStocktakeController::class, 'index']);
        Route::get('asset-stocktakes/{assetStocktake}', [AssetStocktakeController::class, 'show']);
        Route::post('asset-stocktakes', [AssetStocktakeController::class, 'store'])->middleware('permission:asset_stocktake.create,true');
        Route::put('asset-stocktakes/{assetStocktake}', [AssetStocktakeController::class, 'update'])->middleware('permission:asset_stocktake.edit,true');
        Route::delete('asset-stocktakes/{assetStocktake}', [AssetStocktakeController::class, 'destroy'])->middleware('permission:asset_stocktake.delete,true');
        Route::post('asset-stocktakes/export', [AssetStocktakeController::class, 'export']);
    });
});
