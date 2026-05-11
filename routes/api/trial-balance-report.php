<?php

use App\Http\Controllers\TrialBalanceReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('reports/trial-balance', [TrialBalanceReportController::class, 'index']);
    Route::post('reports/trial-balance/export', [TrialBalanceReportController::class, 'export']);
});
