<?php

use App\Http\Controllers\EntityApprovalHistoryController;
use App\Http\Controllers\EntityStateController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PipelineDashboardController;
use App\Http\Controllers\PipelineStateController;
use App\Http\Controllers\PipelineTransitionController;
use Illuminate\Support\Facades\Route;

// --- Pipeline / Workflow ---
Route::middleware('permission:pipeline,true')->group(function () {
    Route::apiResource('pipelines', PipelineController::class);
    Route::post('pipelines/export', [PipelineController::class, 'export']);
    Route::post('pipelines/import', [PipelineController::class, 'import']);
    Route::apiResource('pipelines.states', PipelineStateController::class)->scoped(['state' => 'id'])->except(['show']);
    Route::apiResource('pipelines.transitions', PipelineTransitionController::class)->scoped(['transition' => 'id'])->except(['show']);
    Route::get('entity-states/{entityType}/{entityId}', [EntityStateController::class, 'getState']);
    Route::post('entity-states/{entityType}/{entityId}/transition', [EntityStateController::class, 'executeTransition']);
    Route::get('entity-states/{entityType}/{entityId}/timeline', [EntityStateController::class, 'getTimeline']);
    Route::get('entity-states/{entityType}/{entityId}/approvals', [EntityApprovalHistoryController::class, 'index']);
});
Route::middleware('permission:pipeline_dashboard,true')->group(function () {
    Route::get('pipeline-dashboard/data', [PipelineDashboardController::class, 'getData']);
});
