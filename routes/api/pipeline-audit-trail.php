<?php

use Illuminate\Support\Facades\Route;

Route::get('pipeline-audit-trail', [\App\Http\Controllers\PipelineAuditTrailController::class, 'index'])
    ->middleware('permission:pipeline_audit_trail');

Route::post('pipeline-audit-trail/export', [\App\Http\Controllers\PipelineAuditTrailController::class, 'export'])
    ->middleware('permission:pipeline_audit_trail');
