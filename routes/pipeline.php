<?php

use App\Http\Controllers\PipelineController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('pipelines', function () {
        return Inertia::render('pipelines/index');
    })->name('pipelines');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('/pipelines', [PipelineController::class, 'index']);
    Route::post('/pipelines', [PipelineController::class, 'store']);
    Route::get('/pipelines/{pipeline}', [PipelineController::class, 'show']);
    Route::put('/pipelines/{pipeline}', [PipelineController::class, 'update']);
    Route::delete('/pipelines/{pipeline}', [PipelineController::class, 'destroy']);
    Route::post('/pipelines/export', [PipelineController::class, 'export']);
});
