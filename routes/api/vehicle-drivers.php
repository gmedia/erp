<?php

use App\Http\Controllers\VehicleDriverController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:vehicle_loan.master,true')->group(function () {
    Route::get('vehicle-drivers', [VehicleDriverController::class, 'index']);
    Route::get('vehicle-drivers/{vehicle_driver}', [VehicleDriverController::class, 'show']);
    Route::post('vehicle-drivers', [VehicleDriverController::class, 'store']);
    Route::put('vehicle-drivers/{vehicle_driver}', [VehicleDriverController::class, 'update']);
    Route::delete('vehicle-drivers/{vehicle_driver}', [VehicleDriverController::class, 'destroy']);
});
