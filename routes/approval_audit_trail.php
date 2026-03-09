<?php

use App\Http\Controllers\ApprovalAuditTrailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/approval-audit-trail', [ApprovalAuditTrailController::class, 'index'])
        ->name('approval-audit-trail.index')
        ->middleware('permission:approval_audit_trail');

    Route::post('/api/approval-audit-trail/export', [ApprovalAuditTrailController::class, 'export'])
        ->name('api.approval-audit-trail.export')
        ->middleware('permission:approval_audit_trail.export');
});
