<?php

use App\Http\Controllers\DeviceTokenController;
use Illuminate\Support\Facades\Route;

Route::get('device-tokens', [DeviceTokenController::class, 'index']);
Route::post('device-tokens', [DeviceTokenController::class, 'store']);
Route::delete('device-tokens/{deviceToken}', [DeviceTokenController::class, 'destroy']);
