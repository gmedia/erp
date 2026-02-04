<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('reports')->group(function () {
    Route::get('trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance')->middleware('permission:trial_balance_report');
    Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet')->middleware('permission:balance_sheet_report');
    Route::get('income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement')->middleware('permission:income_statement_report');
});
