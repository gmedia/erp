<?php

use App\Http\Controllers\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:system_health,true')->group(function () {
    Route::get('system-health', [SystemHealthController::class, 'index']);
    Route::post('system-health/run', [SystemHealthController::class, 'runNow'])
        ->middleware('throttle:10,1');
});
