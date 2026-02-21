<?php

use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('reports')->group(function () {
    Route::get('trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance')->middleware('permission:trial_balance_report');
    Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet')->middleware('permission:balance_sheet_report');
    Route::get('income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement')->middleware('permission:income_statement_report');
    Route::get('cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow')->middleware('permission:cash_flow_report');
    Route::get('comparative', [ReportController::class, 'comparative'])->name('reports.comparative')->middleware('permission:comparative_report');

    // Asset Reports
    Route::get('assets/register', [AssetReportController::class, 'register'])->name('reports.assets.register')->middleware('permission:asset');
    Route::post('assets/register/export', [AssetReportController::class, 'exportRegister'])->name('reports.assets.register.export')->middleware('permission:asset');

    Route::get('book-value-depreciation', [\App\Http\Controllers\BookValueDepreciationReportController::class, 'index'])->name('reports.book-value-depreciation')->middleware('permission:asset');
    Route::post('book-value-depreciation/export', [\App\Http\Controllers\BookValueDepreciationReportController::class, 'export'])->name('reports.book-value-depreciation.export')->middleware('permission:asset');
});

