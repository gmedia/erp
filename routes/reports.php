<?php

use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\BookValueDepreciationReportController;
use App\Http\Controllers\InventoryStocktakeVarianceReportController;
use App\Http\Controllers\InventoryValuationReportController;
use App\Http\Controllers\PurchaseHistoryReportController;
use App\Http\Controllers\PurchaseOrderStatusReportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentReportController;
use App\Http\Controllers\StockMovementReportController;
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

    Route::get('book-value-depreciation', [BookValueDepreciationReportController::class, 'index'])->name('reports.book-value-depreciation')->middleware('permission:asset');
    Route::post('book-value-depreciation/export', [BookValueDepreciationReportController::class, 'export'])->name('reports.book-value-depreciation.export')->middleware('permission:asset');

    Route::get('maintenance-cost', [\App\Http\Controllers\MaintenanceCostReportController::class, 'index'])->name('reports.maintenance-cost')->middleware('permission:asset');
    Route::post('maintenance-cost/export', [\App\Http\Controllers\MaintenanceCostReportController::class, 'export'])->name('reports.maintenance-cost.export')->middleware('permission:asset');

    Route::get('inventory-valuation', [InventoryValuationReportController::class, 'index'])->name('reports.inventory-valuation')->middleware('permission:inventory_valuation_report');
    Route::post('inventory-valuation/export', [InventoryValuationReportController::class, 'export'])->name('reports.inventory-valuation.export')->middleware('permission:inventory_valuation_report');

    Route::get('stock-movement', [StockMovementReportController::class, 'index'])->name('reports.stock-movement')->middleware('permission:stock_movement_report');
    Route::post('stock-movement/export', [StockMovementReportController::class, 'export'])->name('reports.stock-movement.export')->middleware('permission:stock_movement_report');

    Route::get('stock-adjustment', [StockAdjustmentReportController::class, 'index'])->name('reports.stock-adjustment')->middleware('permission:stock_adjustment_report');
    Route::post('stock-adjustment/export', [StockAdjustmentReportController::class, 'export'])->name('reports.stock-adjustment.export')->middleware('permission:stock_adjustment_report');

    Route::get('inventory-stocktake-variance', [InventoryStocktakeVarianceReportController::class, 'index'])->name('reports.inventory-stocktake-variance')->middleware('permission:inventory_stocktake_variance_report');
    Route::post('inventory-stocktake-variance/export', [InventoryStocktakeVarianceReportController::class, 'export'])->name('reports.inventory-stocktake-variance.export')->middleware('permission:inventory_stocktake_variance_report');

    Route::get('purchase-order-status', [PurchaseOrderStatusReportController::class, 'index'])->name('reports.purchase-order-status')->middleware('permission:purchase_order_status_report');
    Route::post('purchase-order-status/export', [PurchaseOrderStatusReportController::class, 'export'])->name('reports.purchase-order-status.export')->middleware('permission:purchase_order_status_report');

    Route::get('purchase-history', [PurchaseHistoryReportController::class, 'index'])->name('reports.purchase-history')->middleware('permission:purchase_history_report');
    Route::post('purchase-history/export', [PurchaseHistoryReportController::class, 'export'])->name('reports.purchase-history.export')->middleware('permission:purchase_history_report');
});
