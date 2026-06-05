<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetVarianceReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'permission:budget,true'])->group(function () {
    Route::get('budgets', [BudgetController::class, 'index']);
    Route::get('budgets/{budget}', [BudgetController::class, 'show']);
    Route::post('budgets', [BudgetController::class, 'store']);
    Route::put('budgets/{budget}', [BudgetController::class, 'update']);
    Route::delete('budgets/{budget}', [BudgetController::class, 'destroy']);
    Route::post('budgets/export', [BudgetController::class, 'export']);
    Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve']);
    Route::post('budgets/{budget}/lock', [BudgetController::class, 'lock']);
});

Route::middleware(['auth:sanctum', 'permission:budget_variance_report,true'])->group(function () {
    Route::get('reports/budget-variance', [BudgetVarianceReportController::class, 'index']);
    Route::post('reports/budget-variance/export', [BudgetVarianceReportController::class, 'export']);
});
