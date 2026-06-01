<?php

use App\Http\Controllers\ApprovalMonitoringController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:approval_monitoring,true')->group(function () {
    Route::get('approval-monitoring/data', [ApprovalMonitoringController::class, 'getData']);
});
