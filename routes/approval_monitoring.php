<?php

use App\Http\Controllers\ApprovalMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('approval-monitoring', [ApprovalMonitoringController::class, 'index'])
        ->name('approval-monitoring.index');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('approval-monitoring/data', [ApprovalMonitoringController::class, 'getData'])
        ->name('api.approval-monitoring.data');
});
