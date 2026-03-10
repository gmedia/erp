<?php

use App\Http\Controllers\Api\AuthController;
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

    require __DIR__ . '/api/account-mappings.php';
    require __DIR__ . '/api/accounts.php';
    require __DIR__ . '/api/admin-settings.php';
    require __DIR__ . '/api/approval-audit-trail.php';
    require __DIR__ . '/api/approval-delegations.php';
    require __DIR__ . '/api/approval-flows.php';
    require __DIR__ . '/api/approval-monitoring.php';
    require __DIR__ . '/api/asset-categories.php';
    require __DIR__ . '/api/asset-depreciation-runs.php';
    require __DIR__ . '/api/asset-locations.php';
    require __DIR__ . '/api/asset-maintenances.php';
    require __DIR__ . '/api/asset-models.php';
    require __DIR__ . '/api/asset-movements.php';
    require __DIR__ . '/api/asset-stocktakes.php';
    require __DIR__ . '/api/assets.php';
    require __DIR__ . '/api/branches.php';
    require __DIR__ . '/api/coa-versions.php';
    require __DIR__ . '/api/customer-categories.php';
    require __DIR__ . '/api/customers.php';
    require __DIR__ . '/api/dashboard.php';
    require __DIR__ . '/api/departments.php';
    require __DIR__ . '/api/employees.php';
    require __DIR__ . '/api/fiscal-years.php';
    require __DIR__ . '/api/goods-receipts.php';
    require __DIR__ . '/api/inventory-stocktakes.php';
    require __DIR__ . '/api/journal-entries.php';
    require __DIR__ . '/api/my-approvals.php';
    require __DIR__ . '/api/permissions.php';
    require __DIR__ . '/api/pipeline-audit-trail.php';
    require __DIR__ . '/api/pipeline-dashboard.php';
    require __DIR__ . '/api/pipelines.php';
    require __DIR__ . '/api/positions.php';
    require __DIR__ . '/api/posting-journals.php';
    require __DIR__ . '/api/product-categories.php';
    require __DIR__ . '/api/products.php';
    require __DIR__ . '/api/purchase-orders.php';
    require __DIR__ . '/api/purchase-requests.php';
    require __DIR__ . '/api/reports.php';
    require __DIR__ . '/api/stock-adjustments.php';
    require __DIR__ . '/api/stock-monitor.php';
    require __DIR__ . '/api/stock-movements.php';
    require __DIR__ . '/api/stock-transfers.php';
    require __DIR__ . '/api/supplier-categories.php';
    require __DIR__ . '/api/supplier-returns.php';
    require __DIR__ . '/api/suppliers.php';
    require __DIR__ . '/api/units.php';
    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/warehouses.php';
});
