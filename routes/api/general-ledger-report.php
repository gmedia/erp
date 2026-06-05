<?php

use App\Http\Controllers\GeneralLedgerReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:general_ledger_report,true'])->group(function () {
    Route::get('reports/general-ledger', [GeneralLedgerReportController::class, 'index']);
    Route::post('reports/general-ledger/export', [GeneralLedgerReportController::class, 'export']);
});
