<?php

use App\Http\Controllers\PipelineDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:pipeline_dashboard,true')->group(function () {
    Route::get('pipeline-dashboard/data', [PipelineDashboardController::class, 'getData']);
});
