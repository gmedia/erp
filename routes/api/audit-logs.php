<?php

use App\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Route;

Route::get('audit-logs', [AuditLogController::class, 'index'])
    ->middleware('permission:audit_log');

Route::post('audit-logs/export', [AuditLogController::class, 'export'])
    ->middleware('permission:audit_log.export');
