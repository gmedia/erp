<?php

use App\Http\Controllers\ArAgingReportController;
use App\Http\Controllers\ArOutstandingReportController;
use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\BookValueDepreciationReportController;
use App\Http\Controllers\CustomerStatementReportController;
use App\Http\Controllers\GoodsReceiptReportController;
use App\Http\Controllers\InventoryStocktakeVarianceReportController;
use App\Http\Controllers\InventoryValuationReportController;
use App\Http\Controllers\MaintenanceCostReportController;
use App\Http\Controllers\PurchaseHistoryReportController;
use App\Http\Controllers\PurchaseOrderStatusReportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentReportController;
use App\Http\Controllers\StockMovementReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->group(function () {
    Route::get('trial-balance', [ReportController::class, 'trialBalance'])
        ->middleware('permission:trial_balance_report');
    Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])
        ->middleware('permission:balance_sheet_report');
    Route::get('income-statement', [ReportController::class, 'incomeStatement'])
        ->middleware('permission:income_statement_report');
    Route::get('cash-flow', [ReportController::class, 'cashFlow'])
        ->middleware('permission:cash_flow_report');
    Route::get('comparative', [ReportController::class, 'comparative'])
        ->middleware('permission:comparative_report');
    Route::get('assets/register', [AssetReportController::class, 'register'])
        ->middleware('permission:asset');
    Route::get('book-value-depreciation', [BookValueDepreciationReportController::class, 'index'])
        ->middleware('permission:asset');
    Route::post('book-value-depreciation/export', [BookValueDepreciationReportController::class, 'export'])
        ->middleware('permission:asset');
    Route::get('maintenance-cost', [MaintenanceCostReportController::class, 'index'])
        ->middleware('permission:asset');
    Route::post('maintenance-cost/export', [MaintenanceCostReportController::class, 'export'])
        ->middleware('permission:asset');
    Route::get('inventory-valuation', [InventoryValuationReportController::class, 'index'])
        ->middleware('permission:inventory_valuation_report');
    Route::post('inventory-valuation/export', [InventoryValuationReportController::class, 'export'])
        ->middleware('permission:inventory_valuation_report');
    Route::get('stock-movement', [StockMovementReportController::class, 'index'])
        ->middleware('permission:stock_movement_report');
    Route::post('stock-movement/export', [StockMovementReportController::class, 'export'])
        ->middleware('permission:stock_movement_report');
    Route::get('stock-adjustment', [StockAdjustmentReportController::class, 'index'])
        ->middleware('permission:stock_adjustment_report');
    Route::post('stock-adjustment/export', [StockAdjustmentReportController::class, 'export'])
        ->middleware('permission:stock_adjustment_report');
    Route::get('inventory-stocktake-variance', [InventoryStocktakeVarianceReportController::class, 'index'])
        ->middleware('permission:inventory_stocktake_variance_report');
    Route::post('inventory-stocktake-variance/export', [InventoryStocktakeVarianceReportController::class, 'export'])
        ->middleware('permission:inventory_stocktake_variance_report');
    Route::get('purchase-history', [PurchaseHistoryReportController::class, 'index'])
        ->middleware('permission:purchase_history_report');
    Route::post('purchase-history/export', [PurchaseHistoryReportController::class, 'export'])
        ->middleware('permission:purchase_history_report');
    Route::get('purchase-order-status', [PurchaseOrderStatusReportController::class, 'index'])
        ->middleware('permission:purchase_order_status_report');
    Route::post('purchase-order-status/export', [PurchaseOrderStatusReportController::class, 'export'])
        ->middleware('permission:purchase_order_status_report');
    Route::get('goods-receipt', [GoodsReceiptReportController::class, 'index'])
        ->middleware('permission:goods_receipt_report');
    Route::post('goods-receipt/export', [GoodsReceiptReportController::class, 'export'])
        ->middleware('permission:goods_receipt_report');
    Route::get('ar-aging', [ArAgingReportController::class, 'index'])
        ->middleware('permission:ar_aging_report');
    Route::post('ar-aging/export', [ArAgingReportController::class, 'export'])
        ->middleware('permission:ar_aging_report');
    Route::get('ar-outstanding', [ArOutstandingReportController::class, 'index'])
        ->middleware('permission:ar_outstanding_report');
    Route::post('ar-outstanding/export', [ArOutstandingReportController::class, 'export'])
        ->middleware('permission:ar_outstanding_report');
    Route::get('customer-statement', [CustomerStatementReportController::class, 'index'])
        ->middleware('permission:customer_statement_report');
    Route::post('customer-statement/export', [CustomerStatementReportController::class, 'export'])
        ->middleware('permission:customer_statement_report');
});
