<?php

use App\Http\Controllers\AssetModelController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:asset_model,true')->group(function () {
    Route::get('asset-models', [AssetModelController::class, 'index']);

    Route::get('asset-models/{asset_model}', [AssetModelController::class, 'show']);

    Route::post('asset-models', [AssetModelController::class, 'store'])
        ->middleware('permission:asset_model.create,true');

    Route::put('asset-models/{asset_model}', [AssetModelController::class, 'update'])
        ->middleware('permission:asset_model.edit,true');

    Route::delete('asset-models/{asset_model}', [AssetModelController::class, 'destroy'])
        ->middleware('permission:asset_model.delete,true');
    Route::post('asset-models/export', [AssetModelController::class, 'export']);
    Route::post('asset-models/import', [AssetModelController::class, 'import'])
        ->middleware('permission:asset_model.create,true');
});
