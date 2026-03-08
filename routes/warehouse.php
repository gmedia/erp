<?php

use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:warehouse,true')->group(function () {
        Route::get('warehouses', [WarehouseController::class, 'index']);
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
        Route::post('warehouses', [WarehouseController::class, 'store'])->middleware('permission:warehouse.create,true');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->middleware('permission:warehouse.edit,true');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->middleware('permission:warehouse.delete,true');
        Route::post('warehouses/export', [WarehouseController::class, 'export']);
    });
});
