<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountMappingController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ApprovalDelegationController;
use App\Http\Controllers\ApprovalFlowController;
use App\Http\Controllers\ApprovalMonitoringController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDashboardController;
use App\Http\Controllers\AssetDepreciationRunController;
use App\Http\Controllers\AssetLocationController;
use App\Http\Controllers\AssetMaintenanceController;
use App\Http\Controllers\AssetModelController;
use App\Http\Controllers\AssetMovementController;
use App\Http\Controllers\AssetStocktakeController;
use App\Http\Controllers\AssetStocktakeItemController;
use App\Http\Controllers\AssetStocktakeVarianceController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CoaVersionController;
use App\Http\Controllers\CustomerCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EntityApprovalHistoryController;
use App\Http\Controllers\EntityStateController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\InventoryStocktakeController;
use App\Http\Controllers\InventoryStocktakeItemController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\MyApprovalController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\PipelineDashboardController;
use App\Http\Controllers\PipelineStateController;
use App\Http\Controllers\PipelineTransitionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\PostingJournalController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\BookValueDepreciationReportController;
use App\Http\Controllers\InventoryStocktakeVarianceReportController;
use App\Http\Controllers\InventoryValuationReportController;
use App\Http\Controllers\MaintenanceCostReportController;
use App\Http\Controllers\StockAdjustmentReportController;
use App\Http\Controllers\StockMovementReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockAdjustmentItemController;
use App\Http\Controllers\StockMonitorController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockTransferItemController;
use App\Http\Controllers\SupplierCategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// All routes here are automatically prefixed with /api/ by Laravel
    // === AUTH (public) ===
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    // === PROTECTED ===
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // --- Master Data ---
        Route::middleware('permission:department,true')->group(function () {
            Route::apiResource('departments', DepartmentController::class);
            Route::post('departments/export', [DepartmentController::class, 'export']);
            Route::post('departments/import', [DepartmentController::class, 'import']);
        });
        Route::middleware('permission:position,true')->group(function () {
            Route::apiResource('positions', PositionController::class);
            Route::post('positions/export', [PositionController::class, 'export']);
            Route::post('positions/import', [PositionController::class, 'import']);
        });
        Route::middleware('permission:branch,true')->group(function () {
            Route::apiResource('branches', BranchController::class);
            Route::post('branches/export', [BranchController::class, 'export']);
            Route::post('branches/import', [BranchController::class, 'import']);
        });
        Route::middleware('permission:employee,true')->group(function () {
            Route::apiResource('employees', EmployeeController::class);
            Route::post('employees/export', [EmployeeController::class, 'export']);
            Route::post('employees/import', [EmployeeController::class, 'import']);
        });
        Route::middleware('permission:unit,true')->group(function () {
            Route::apiResource('units', UnitController::class);
            Route::post('units/export', [UnitController::class, 'export']);
            Route::post('units/import', [UnitController::class, 'import']);
        });
        Route::middleware('permission:warehouse,true')->group(function () {
            Route::apiResource('warehouses', \App\Http\Controllers\WarehouseController::class);
            Route::post('warehouses/export', [\App\Http\Controllers\WarehouseController::class, 'export']);
            Route::post('warehouses/import', [\App\Http\Controllers\WarehouseController::class, 'import']);
        });

        // --- Customer & Supplier ---
        Route::middleware('permission:customer,true')->group(function () {
            Route::apiResource('customers', CustomerController::class);
            Route::post('customers/export', [CustomerController::class, 'export']);
            Route::post('customers/import', [CustomerController::class, 'import']);
        });
        Route::middleware('permission:customer_category,true')->group(function () {
            Route::apiResource('customer-categories', CustomerCategoryController::class);
            Route::post('customer-categories/export', [CustomerCategoryController::class, 'export']);
            Route::post('customer-categories/import', [CustomerCategoryController::class, 'import']);
        });
        Route::middleware('permission:supplier,true')->group(function () {
            Route::apiResource('suppliers', SupplierController::class);
            Route::post('suppliers/export', [SupplierController::class, 'export']);
            Route::post('suppliers/import', [SupplierController::class, 'import']);
        });
        Route::middleware('permission:supplier_category,true')->group(function () {
            Route::apiResource('supplier-categories', SupplierCategoryController::class);
            Route::post('supplier-categories/export', [SupplierCategoryController::class, 'export']);
            Route::post('supplier-categories/import', [SupplierCategoryController::class, 'import']);
        });

        // --- Products & Inventory ---
        Route::middleware('permission:product,true')->group(function () {
            Route::apiResource('products', ProductController::class);
            Route::post('products/export', [ProductController::class, 'export']);
            Route::post('products/import', [ProductController::class, 'import']);
        });
        Route::middleware('permission:product_category,true')->group(function () {
            Route::apiResource('product-categories', ProductCategoryController::class);
            Route::post('product-categories/export', [ProductCategoryController::class, 'export']);
            Route::post('product-categories/import', [ProductCategoryController::class, 'import']);
        });

        // Stock Transfer (with nested items)
        Route::middleware('permission:stock_transfer,true')->group(function () {
            Route::apiResource('stock-transfers', StockTransferController::class);
            Route::post('stock-transfers/export', [StockTransferController::class, 'export']);
            Route::post('stock-transfers/import', [StockTransferController::class, 'import']);
            Route::get('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'getItems']);
            Route::post('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'syncItems']);
        });

        // Stock Adjustment (with nested items)
        Route::middleware('permission:stock_adjustment,true')->group(function () {
            Route::apiResource('stock-adjustments', StockAdjustmentController::class);
            Route::post('stock-adjustments/export', [StockAdjustmentController::class, 'export']);
            Route::post('stock-adjustments/import', [StockAdjustmentController::class, 'import']);
            Route::get('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'getItems']);
            Route::post('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'syncItems']);
        });

        // Inventory Stocktake (with nested items)
        Route::middleware('permission:inventory_stocktake,true')->group(function () {
            Route::apiResource('inventory-stocktakes', InventoryStocktakeController::class);
            Route::post('inventory-stocktakes/export', [InventoryStocktakeController::class, 'export']);
            Route::post('inventory-stocktakes/import', [InventoryStocktakeController::class, 'import']);
            Route::get('inventory-stocktakes/{inventoryStocktake}/items', [InventoryStocktakeItemController::class, 'getItems']);
            Route::post('inventory-stocktakes/{inventoryStocktake}/items', [InventoryStocktakeItemController::class, 'syncItems']);
        });

        // Stock Monitor & Movement (read-only)
        Route::middleware('permission:stock_monitor,true')->group(function () {
            Route::get('stock-monitor', [StockMonitorController::class, 'index']);
            Route::post('stock-monitor/export', [StockMonitorController::class, 'export']);
        });
        Route::middleware('permission:stock_movement,true')->group(function () {
            Route::get('stock-movements', [StockMovementController::class, 'index']);
            Route::post('stock-movements/export', [StockMovementController::class, 'export']);
        });

        // --- Accounting ---
        Route::middleware('permission:fiscal_year,true')->group(function () {
            Route::apiResource('fiscal-years', FiscalYearController::class);
            Route::post('fiscal-years/export', [FiscalYearController::class, 'export']);
            Route::post('fiscal-years/import', [FiscalYearController::class, 'import']);
        });
        Route::middleware('permission:coa_version,true')->group(function () {
            Route::apiResource('coa-versions', CoaVersionController::class);
            Route::post('coa-versions/export', [CoaVersionController::class, 'export']);
            Route::post('coa-versions/import', [CoaVersionController::class, 'import']);
        });
        Route::middleware('permission:account,true')->group(function () {
            Route::apiResource('accounts', AccountController::class);
            Route::post('accounts/export', [AccountController::class, 'export']);
            Route::post('accounts/import', [AccountController::class, 'import']);
        });
        Route::middleware('permission:account_mapping,true')->group(function () {
            Route::apiResource('account-mappings', AccountMappingController::class);
            Route::post('account-mappings/export', [AccountMappingController::class, 'export']);
            Route::post('account-mappings/import', [AccountMappingController::class, 'import']);
        });
        Route::middleware('permission:journal_entry,true')->group(function () {
            Route::apiResource('journal-entries', JournalEntryController::class);
            Route::post('journal-entries/export', [JournalEntryController::class, 'export']);
            Route::post('journal-entries/import', [JournalEntryController::class, 'import']);
        });
        Route::middleware('permission:posting_journal,true')->group(function () {
            Route::get('posting-journals', [PostingJournalController::class, 'index']);
            Route::post('posting-journals/post', [PostingJournalController::class, 'post']);
        });

        // --- Assets ---
        Route::middleware('permission:asset,true')->group(function () {
            Route::apiResource('assets', AssetController::class);
            Route::get('assets/{asset}/profile', [AssetController::class, 'profile']);
            Route::post('assets/export', [AssetController::class, 'export']);
            Route::post('assets/import', [AssetController::class, 'import']);
            Route::get('asset-dashboard/data', [AssetDashboardController::class, 'getData']);
        });
        Route::middleware('permission:asset_category,true')->group(function () {
            Route::apiResource('asset-categories', AssetCategoryController::class);
            Route::post('asset-categories/export', [AssetCategoryController::class, 'export']);
            Route::post('asset-categories/import', [AssetCategoryController::class, 'import']);
        });
        Route::middleware('permission:asset_model,true')->group(function () {
            Route::apiResource('asset-models', AssetModelController::class);
            Route::post('asset-models/export', [AssetModelController::class, 'export']);
            Route::post('asset-models/import', [AssetModelController::class, 'import']);
        });
        Route::middleware('permission:asset_location,true')->group(function () {
            Route::apiResource('asset-locations', AssetLocationController::class);
            Route::post('asset-locations/export', [AssetLocationController::class, 'export']);
            Route::post('asset-locations/import', [AssetLocationController::class, 'import']);
        });
        Route::middleware('permission:asset_movement,true')->group(function () {
            Route::apiResource('asset-movements', AssetMovementController::class);
            Route::post('asset-movements/export', [AssetMovementController::class, 'export']);
            Route::post('asset-movements/import', [AssetMovementController::class, 'import']);
        });
        Route::middleware('permission:asset_maintenance,true')->group(function () {
            Route::apiResource('asset-maintenances', AssetMaintenanceController::class);
            Route::post('asset-maintenances/export', [AssetMaintenanceController::class, 'export']);
            Route::post('asset-maintenances/import', [AssetMaintenanceController::class, 'import']);
        });

        // Asset Stocktake (with nested items + variances)
        Route::middleware('permission:asset_stocktake,true')->group(function () {
            Route::apiResource('asset-stocktakes', AssetStocktakeController::class);
            Route::post('asset-stocktakes/export', [AssetStocktakeController::class, 'export']);
            Route::post('asset-stocktakes/import', [AssetStocktakeController::class, 'import']);
            Route::get('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'getItems']);
            Route::post('asset-stocktakes/{assetStocktake}/items', [AssetStocktakeItemController::class, 'syncItems']);
            Route::get('asset-stocktake-variances', [AssetStocktakeVarianceController::class, 'index']);
            Route::post('asset-stocktake-variances/export', [AssetStocktakeVarianceController::class, 'export']);
        });

        // Asset Depreciation
        Route::middleware('permission:asset_depreciation_run,true')->group(function () {
            Route::get('asset-depreciation-runs', [AssetDepreciationRunController::class, 'apiIndex']);
            Route::post('asset-depreciation-runs/calculate', [AssetDepreciationRunController::class, 'calculate']);
            Route::get('asset-depreciation-runs/{assetDepreciationRun}/lines', [AssetDepreciationRunController::class, 'lines']);
            Route::post('asset-depreciation-runs/{assetDepreciationRun}/post', [AssetDepreciationRunController::class, 'postToJournal']);
        });

        // --- Pipeline / Workflow ---
        Route::middleware('permission:pipeline,true')->group(function () {
            Route::apiResource('pipelines', PipelineController::class);
            Route::post('pipelines/export', [PipelineController::class, 'export']);
            Route::post('pipelines/import', [PipelineController::class, 'import']);
            Route::apiResource('pipelines.states', PipelineStateController::class)->scoped(['state' => 'id'])->except(['show']);
            Route::apiResource('pipelines.transitions', PipelineTransitionController::class)->scoped(['transition' => 'id'])->except(['show']);
            Route::get('entity-states/{entityType}/{entityId}', [EntityStateController::class, 'getState']);
            Route::post('entity-states/{entityType}/{entityId}/transition', [EntityStateController::class, 'executeTransition']);
            Route::get('entity-states/{entityType}/{entityId}/timeline', [EntityStateController::class, 'getTimeline']);
            Route::get('entity-states/{entityType}/{entityId}/approvals', [EntityApprovalHistoryController::class, 'index']);
        });
        Route::middleware('permission:pipeline_dashboard,true')->group(function () {
            Route::get('pipeline-dashboard/data', [PipelineDashboardController::class, 'getData']);
        });

        // --- Approvals ---
        Route::middleware('permission:approval_flow,true')->group(function () {
            Route::apiResource('approval-flows', ApprovalFlowController::class);
            Route::post('approval-flows/export', [ApprovalFlowController::class, 'export']);
            Route::post('approval-flows/import', [ApprovalFlowController::class, 'import']);
        });
        Route::middleware('permission:approval_delegation,true')->group(function () {
            Route::apiResource('approval-delegations', ApprovalDelegationController::class);
            Route::post('approval-delegations/export', [ApprovalDelegationController::class, 'export']);
            Route::post('approval-delegations/import', [ApprovalDelegationController::class, 'import']);
        });
        Route::get('approval-monitoring/data', [ApprovalMonitoringController::class, 'getData']);

        // My Approvals
        Route::get('my-approvals', [MyApprovalController::class, 'index']);
        Route::post('my-approvals/{approvalRequest}/approve', [MyApprovalController::class, 'approve']);
        Route::post('my-approvals/{approvalRequest}/reject', [MyApprovalController::class, 'reject']);

        // --- Reports ---
        Route::prefix('reports')->group(function () {
            Route::get('trial-balance', [ReportController::class, 'trialBalance'])->middleware('permission:trial_balance_report');
            Route::get('balance-sheet', [ReportController::class, 'balanceSheet'])->middleware('permission:balance_sheet_report');
            Route::get('income-statement', [ReportController::class, 'incomeStatement'])->middleware('permission:income_statement_report');
            Route::get('cash-flow', [ReportController::class, 'cashFlow'])->middleware('permission:cash_flow_report');
            Route::get('comparative', [ReportController::class, 'comparative'])->middleware('permission:comparative_report');
            Route::get('assets/register', [AssetReportController::class, 'register'])->middleware('permission:asset');
            Route::get('book-value-depreciation', [BookValueDepreciationReportController::class, 'index'])->middleware('permission:asset');
            Route::get('maintenance-cost', [MaintenanceCostReportController::class, 'index'])->middleware('permission:asset');
            Route::get('inventory-valuation', [InventoryValuationReportController::class, 'index'])->middleware('permission:inventory_valuation_report');
            Route::post('inventory-valuation/export', [InventoryValuationReportController::class, 'export'])->middleware('permission:inventory_valuation_report');
            Route::get('stock-movement', [StockMovementReportController::class, 'index'])->middleware('permission:stock_movement_report');
            Route::post('stock-movement/export', [StockMovementReportController::class, 'export'])->middleware('permission:stock_movement_report');
            Route::get('stock-adjustment', [StockAdjustmentReportController::class, 'index'])->middleware('permission:stock_adjustment_report');
            Route::post('stock-adjustment/export', [StockAdjustmentReportController::class, 'export'])->middleware('permission:stock_adjustment_report');
            Route::get('inventory-stocktake-variance', [InventoryStocktakeVarianceReportController::class, 'index'])->middleware('permission:inventory_stocktake_variance_report');
            Route::post('inventory-stocktake-variance/export', [InventoryStocktakeVarianceReportController::class, 'export'])->middleware('permission:inventory_stocktake_variance_report');
        });

        // --- Users & Permissions ---
        Route::get('users', [UserController::class, 'apiIndex']);
        Route::get('users/{user}', [UserController::class, 'apiShow']);
        Route::group(['middleware' => ['permission:permission']], function () {
            Route::get('permissions', [\App\Http\Controllers\PermissionController::class, 'index']);
            Route::get('employees/{employee}/permissions', [EmployeeController::class, 'permissions']);
            Route::post('employees/{employee}/permissions', [EmployeeController::class, 'syncPermissions']);
        });

        // Employee User Management
        Route::group(['middleware' => ['permission:user']], function () {
            Route::get('employees/{employee}/user', [\App\Http\Controllers\UserController::class, 'getUserByEmployee']);
            Route::post('employees/{employee}/user', [\App\Http\Controllers\UserController::class, 'updateUser']);
        });

        // --- Admin Settings ---
        Route::middleware('permission:admin_setting,true')->group(function () {
            Route::get('admin-settings', [AdminSettingController::class, 'index']);
            Route::put('admin-settings', [AdminSettingController::class, 'update']);
            Route::post('admin-settings/test-smtp', [AdminSettingController::class, 'testSmtp']);
        });

        // --- Dashboard ---
        Route::get('dashboard', [DashboardController::class, 'index']);
    });

