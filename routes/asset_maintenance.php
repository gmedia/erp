<?php

use App\Http\Controllers\AssetMaintenanceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('asset-maintenances', function () {
        return Inertia::render('asset-maintenances/index');
    })->name('asset-maintenances')->middleware('permission:asset_maintenance');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:asset_maintenance,true')->group(function () {
        Route::get('asset-maintenances', [AssetMaintenanceController::class, 'index'])->name('asset-maintenances.index');
        Route::get('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'show'])->name('asset-maintenances.show');
        Route::post('asset-maintenances', [AssetMaintenanceController::class, 'store'])->name('asset-maintenances.store')->middleware('permission:asset_maintenance.create,true');
        Route::put('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'update'])->name('asset-maintenances.update')->middleware('permission:asset_maintenance.edit,true');
        Route::delete('asset-maintenances/{asset_maintenance}', [AssetMaintenanceController::class, 'destroy'])->name('asset-maintenances.destroy')->middleware('permission:asset_maintenance.delete,true');
        Route::post('asset-maintenances/export', [AssetMaintenanceController::class, 'export'])->name('asset-maintenances.export');
    });
});
