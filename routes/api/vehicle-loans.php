<?php

use App\Http\Controllers\VehicleLoanController;
use App\Http\Controllers\VehicleLoanPhotoController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:vehicle_loan,true')->group(function () {
    Route::get('vehicle-loans', [VehicleLoanController::class, 'index']);
    Route::get('vehicle-loans/my', [VehicleLoanController::class, 'my']);

    Route::middleware('permission:vehicle_loan.security,true')->group(function () {
        Route::get('vehicle-loans/security-queue', [VehicleLoanController::class, 'securityQueue']);
        Route::post('vehicle-loans/pickups/{vehicle_loan_pickup}/handover', [
            VehicleLoanController::class, 'handoverKey',
        ]);
    });

    Route::get('vehicle-loans/photos/pickups/{vehicle_loan_pickup}', [
        VehicleLoanPhotoController::class, 'pickup',
    ]);
    Route::get('vehicle-loans/photos/returns/{vehicle_loan_return}/handover', [
        VehicleLoanPhotoController::class, 'handover',
    ]);
    Route::get('vehicle-loans/photos/returns/{vehicle_loan_return}/condition', [
        VehicleLoanPhotoController::class, 'condition',
    ]);

    Route::get('vehicle-loans/{vehicle_loan}', [VehicleLoanController::class, 'show']);
    Route::post('vehicle-loans', [VehicleLoanController::class, 'store']);
    Route::put('vehicle-loans/{vehicle_loan}', [VehicleLoanController::class, 'update']);
    Route::delete('vehicle-loans/{vehicle_loan}', [VehicleLoanController::class, 'destroy']);

    // Submit is validated against the stored draft in the controller.
    Route::post('vehicle-loans/{vehicle_loan}/submit', [VehicleLoanController::class, 'submit']);

    Route::post('vehicle-loans/{vehicle_loan}/review-assignment', [
        VehicleLoanController::class, 'reviewAssignment',
    ])->middleware('permission:my_approvals,true');

    Route::post('vehicle-loans/{vehicle_loan}/pickups', [
        VehicleLoanController::class, 'storePickup',
    ])->middleware('permission:vehicle_loan.pickup,true');

    Route::post('vehicle-loans/pickups/{vehicle_loan_pickup}/returns', [
        VehicleLoanController::class, 'storeReturn',
    ])->middleware('permission:vehicle_loan.return,true');
});
