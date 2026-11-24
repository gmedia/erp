<?php

use App\Http\Controllers\EntityApprovalHistoryController;
use App\Http\Controllers\EntityStateController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PipelineStateController;
use App\Http\Controllers\PipelineTransitionController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:pipeline,true')->group(function () {
    Route::get('pipelines', [PipelineController::class, 'index']);

    Route::get('pipelines/{pipeline}', [PipelineController::class, 'show']);

    Route::post('pipelines', [PipelineController::class, 'store'])
        ->middleware('permission:pipeline.create,true');

    Route::put('pipelines/{pipeline}', [PipelineController::class, 'update'])
        ->middleware('permission:pipeline.edit,true');

    Route::delete('pipelines/{pipeline}', [PipelineController::class, 'destroy'])
        ->middleware('permission:pipeline.delete,true');
    Route::post('pipelines/export', [PipelineController::class, 'export']);
    Route::post('pipelines/import', [PipelineController::class, 'import'])
        ->middleware('permission:pipeline.create,true');
    Route::apiResource('pipelines.states', PipelineStateController::class)
        ->scoped(['state' => 'id'])
        ->only(['index']);
    Route::apiResource('pipelines.transitions', PipelineTransitionController::class)
        ->scoped(['transition' => 'id'])
        ->only(['index']);

    Route::middleware('permission:pipeline.edit,true')->group(function () {
        Route::apiResource('pipelines.states', PipelineStateController::class)
            ->scoped(['state' => 'id'])
            ->except(['index', 'show']);
        Route::apiResource('pipelines.transitions', PipelineTransitionController::class)
            ->scoped(['transition' => 'id'])
            ->except(['index', 'show']);
    });
});

// Entity-state endpoints: `pipeline` permission, or the document module permission
// for entity types listed in CheckEntityStatePermission (purchase_order, purchase_request).
Route::middleware('entity-state-permission')->group(function () {
    Route::get('entity-states/{entityType}/{entityId}', [EntityStateController::class, 'getState']);
    Route::post(
        'entity-states/{entityType}/{entityId}/transition',
        [EntityStateController::class, 'executeTransition']
    );
    Route::get('entity-states/{entityType}/{entityId}/timeline', [EntityStateController::class, 'getTimeline']);
    Route::get('entity-states/{entityType}/{entityId}/approvals', [EntityApprovalHistoryController::class, 'index']);
});
