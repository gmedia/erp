<?php

use App\Http\Controllers\AssetLocationController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:asset_location,true')->group(function () {
    Route::get('asset-locations', [AssetLocationController::class, 'index']);

    Route::get('asset-locations/{asset_location}', [AssetLocationController::class, 'show']);

    Route::post('asset-locations', [AssetLocationController::class, 'store'])
        ->middleware('permission:asset_location.create,true');

    Route::put('asset-locations/{asset_location}', [AssetLocationController::class, 'update'])
        ->middleware('permission:asset_location.edit,true');

    Route::delete('asset-locations/{asset_location}', [AssetLocationController::class, 'destroy'])
        ->middleware('permission:asset_location.delete,true');
    Route::post('asset-locations/export', [AssetLocationController::class, 'export']);
    Route::post('asset-locations/import', [AssetLocationController::class, 'import'])
        ->middleware('permission:asset_location.create,true');
});
