<?php

use App\Http\Controllers\VehicleUnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:vehicle_loan.master,true')->group(function () {
    Route::get('vehicle-units', [VehicleUnitController::class, 'index']);
    Route::get('vehicle-units/{vehicle_unit}', [VehicleUnitController::class, 'show']);
    Route::post('vehicle-units', [VehicleUnitController::class, 'store']);
    Route::put('vehicle-units/{vehicle_unit}', [VehicleUnitController::class, 'update']);
    Route::delete('vehicle-units/{vehicle_unit}', [VehicleUnitController::class, 'destroy']);
});
