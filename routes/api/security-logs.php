<?php

use App\Http\Controllers\SecurityLogController;
use Illuminate\Support\Facades\Route;

Route::get('security-logs', [SecurityLogController::class, 'index'])
    ->middleware('permission:security_log');

Route::post('security-logs/export', [SecurityLogController::class, 'export'])
    ->middleware('permission:security_log.export');
