<?php

use App\Http\Controllers\AssetMaintenanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:asset_maintenance,true')->group(function () {
    Route::get('asset-maintenances', [AssetMaintenanceController::class, 'index']);

    Route::get('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'show']);

    Route::post('asset-maintenances', [AssetMaintenanceController::class, 'store'])
        ->middleware('permission:asset_maintenance.create,true');

    Route::put('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'update'])
        ->middleware('permission:asset_maintenance.edit,true');

    Route::delete('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'destroy'])
        ->middleware('permission:asset_maintenance.delete,true');
    Route::post('asset-maintenances/export', [AssetMaintenanceController::class, 'export']);
    Route::post('asset-maintenances/import', [AssetMaintenanceController::class, 'import'])
        ->middleware('permission:asset_maintenance.create,true');
});
