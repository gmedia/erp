<?php

use Illuminate\Support\Facades\Route;

Route::middleware('permission:warehouse,true')->group(function () {
    Route::get('warehouses', [\App\Http\Controllers\WarehouseController::class, 'index']);

    Route::get('warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'show']);

    Route::post('warehouses', [\App\Http\Controllers\WarehouseController::class, 'store'])
        ->middleware('permission:warehouse.create,true');

    Route::put('warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'update'])
        ->middleware('permission:warehouse.edit,true');

    Route::delete('warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'destroy'])
        ->middleware('permission:warehouse.delete,true');
    Route::post('warehouses/export', [\App\Http\Controllers\WarehouseController::class, 'export']);
    Route::post('warehouses/import', [\App\Http\Controllers\WarehouseController::class, 'import'])
        ->middleware('permission:warehouse.create,true');
});
