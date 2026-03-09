<?php

use App\Http\Controllers\AssetMovementController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:asset_movement,true')->group(function () {
    Route::get('asset-movements', [AssetMovementController::class, 'index']);

    Route::get('asset-movements/{asset_movement}', [AssetMovementController::class, 'show']);

    Route::post('asset-movements', [AssetMovementController::class, 'store'])->middleware('permission:asset_movement.create,true');

    Route::put('asset-movements/{asset_movement}', [AssetMovementController::class, 'update'])->middleware('permission:asset_movement.edit,true');

    Route::delete('asset-movements/{asset_movement}', [AssetMovementController::class, 'destroy'])->middleware('permission:asset_movement.delete,true');
    Route::post('asset-movements/export', [AssetMovementController::class, 'export']);
    Route::post('asset-movements/import', [AssetMovementController::class, 'import'])->middleware('permission:asset_movement.create,true');
});
