<?php

use App\Http\Controllers\TrialBalanceReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('reports')->group(function () {
    Route::get('trial-balance-detailed', [TrialBalanceReportController::class, 'index']);
    Route::post('trial-balance-detailed/export', [TrialBalanceReportController::class, 'export']);
});
