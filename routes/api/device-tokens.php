<?php

use App\Http\Controllers\DeviceTokenController;
use Illuminate\Support\Facades\Route;

Route::get('device-tokens', [DeviceTokenController::class, 'index']);
Route::post('device-tokens', [DeviceTokenController::class, 'store'])
    ->middleware('throttle:10,1');
Route::delete('device-tokens/{deviceToken}', [DeviceTokenController::class, 'destroy']);
