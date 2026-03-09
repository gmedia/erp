<?php

use Illuminate\Support\Facades\Route;

Route::get('approval-audit-trail', [\App\Http\Controllers\ApprovalAuditTrailController::class, 'index'])
    ->middleware('permission:approval_audit_trail');

Route::post('approval-audit-trail/export', [\App\Http\Controllers\ApprovalAuditTrailController::class, 'export'])
    ->middleware('permission:approval_audit_trail.export');
