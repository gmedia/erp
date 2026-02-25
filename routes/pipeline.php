<?php

use App\Http\Controllers\PipelineController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('pipelines', function () {
        return Inertia::render('pipelines/index');
    })->name('pipelines')->middleware('permission:pipeline');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {    
    Route::middleware('permission:pipeline,true')->group(function () {        
        Route::get('/pipelines', [PipelineController::class, 'index']);
        Route::post('/pipelines', [PipelineController::class, 'store'])->middleware('permission:pipeline.create,true');
        Route::get('/pipelines/{pipeline}', [PipelineController::class, 'show']);
        Route::put('/pipelines/{pipeline}', [PipelineController::class, 'update'])->middleware('permission:pipeline.edit,true');
        Route::delete('/pipelines/{pipeline}', [PipelineController::class, 'destroy'])->middleware('permission:pipeline.delete,true');
        Route::post('/pipelines/export', [PipelineController::class, 'export']);

        // Pipeline States nested routes
        Route::apiResource('pipelines.states', \App\Http\Controllers\PipelineStateController::class)
            ->scoped(['state' => 'id'])
            ->except(['show']);

        // Pipeline Transitions nested routes
        Route::apiResource('pipelines.transitions', \App\Http\Controllers\PipelineTransitionController::class)
            ->scoped(['transition' => 'id'])
            ->except(['show']);
    });
});
