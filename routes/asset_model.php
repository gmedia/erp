<?php

use App\Http\Controllers\AssetModelController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('asset-models', function () {
        return Inertia::render('asset-models/index');
    })->name('asset-models')->middleware('permission:asset_model');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_model,true')->group(function () {
        Route::get('asset-models', [AssetModelController::class, 'index']);
        Route::get('asset-models/{assetModel}', [AssetModelController::class, 'show']);
        Route::post('asset-models', [AssetModelController::class, 'store'])->middleware('permission:asset_model.create,true');
        Route::put('asset-models/{assetModel}', [AssetModelController::class, 'update'])->middleware('permission:asset_model.edit,true');
        Route::delete('asset-models/{assetModel}', [AssetModelController::class, 'destroy'])->middleware('permission:asset_model.delete,true');
        Route::post('asset-models/export', [AssetModelController::class, 'export']);
    });
});
