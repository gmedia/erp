<?php

use App\Http\Controllers\PipelineAuditTrailController;
use Illuminate\Support\Facades\Route;

Route::get('pipeline-audit-trail', [PipelineAuditTrailController::class, 'index'])
    ->middleware('permission:pipeline_audit_trail');

Route::post('pipeline-audit-trail/export', [PipelineAuditTrailController::class, 'export'])
    ->middleware('permission:pipeline_audit_trail');
