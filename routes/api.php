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
use App\Http\Controllers\AssetReportController;
use App\Http\Controllers\AssetStocktakeController;
use App\Http\Controllers\AssetStocktakeItemController;
use App\Http\Controllers\AssetStocktakeVarianceController;
use App\Http\Controllers\BookValueDepreciationReportController;
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
use App\Http\Controllers\InventoryStocktakeVarianceReportController;
use App\Http\Controllers\InventoryValuationReportController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\MaintenanceCostReportController;
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
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockAdjustmentItemController;
use App\Http\Controllers\StockAdjustmentReportController;
use App\Http\Controllers\StockMonitorController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockMovementReportController;
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
    require __DIR__ . '/api/master_data.php';

        // --- Customer Supplier ---
    require __DIR__ . '/api/customer_supplier.php';

        // --- Products Inventory ---
    require __DIR__ . '/api/products_inventory.php';

        // --- Accounting ---
    require __DIR__ . '/api/accounting.php';

        // --- Assets ---
    require __DIR__ . '/api/assets.php';

        // --- Pipeline Workflow ---
    require __DIR__ . '/api/pipeline_workflow.php';

        // --- Approvals ---
    require __DIR__ . '/api/approvals.php';

        // --- Reports ---
    require __DIR__ . '/api/reports.php';

        // --- Users Permissions ---
    require __DIR__ . '/api/users_permissions.php';

        // --- Admin Settings ---
    require __DIR__ . '/api/admin_settings.php';

        // --- Dashboard ---
    require __DIR__ . '/api/dashboard.php';
});

