<?php

use App\Http\Controllers\ReportConfigurationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:report_configuration,true'])->group(function () {
    Route::get('report-configurations', [ReportConfigurationController::class, 'index']);
    Route::get('report-configurations/{report_configuration}', [ReportConfigurationController::class, 'show']);
    Route::post('report-configurations', [ReportConfigurationController::class, 'store']);
    Route::put('report-configurations/{report_configuration}', [ReportConfigurationController::class, 'update']);
    Route::delete('report-configurations/{report_configuration}', [ReportConfigurationController::class, 'destroy']);
    Route::post('report-configurations/export', [ReportConfigurationController::class, 'export']);
});
