<?php

use App\Http\Controllers\ApprovalAuditTrailController;
use Illuminate\Support\Facades\Route;

Route::get('approval-audit-trail', [ApprovalAuditTrailController::class, 'index'])
    ->middleware('permission:approval_audit_trail');

Route::post('approval-audit-trail/export', [ApprovalAuditTrailController::class, 'export'])
    ->middleware('permission:approval_audit_trail.export');
