<?php

use App\Http\Controllers\ApprovalMonitoringController;
use Illuminate\Support\Facades\Route;

Route::get('approval-monitoring/data', [ApprovalMonitoringController::class, 'getData']);
