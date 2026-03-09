<?php

use App\Http\Controllers\ApprovalDelegationController;
use App\Http\Controllers\ApprovalFlowController;
use App\Http\Controllers\ApprovalMonitoringController;
use App\Http\Controllers\MyApprovalController;
use Illuminate\Support\Facades\Route;

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
