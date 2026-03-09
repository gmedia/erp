<?php

use App\Http\Controllers\ApprovalDelegationController;
use App\Http\Controllers\ApprovalFlowController;
use App\Http\Controllers\ApprovalMonitoringController;
use App\Http\Controllers\MyApprovalController;
use Illuminate\Support\Facades\Route;

// --- Approvals ---
Route::middleware('permission:approval_flow,true')->group(function () {
    Route::get('approval-flows', [ApprovalFlowController::class, 'index']);

    Route::get('approval-flows/{approval_flow}', [ApprovalFlowController::class, 'show']);

    Route::post('approval-flows', [ApprovalFlowController::class, 'store'])->middleware('permission:approval_flow.create,true');

    Route::put('approval-flows/{approval_flow}', [ApprovalFlowController::class, 'update'])->middleware('permission:approval_flow.edit,true');

    Route::delete('approval-flows/{approval_flow}', [ApprovalFlowController::class, 'destroy'])->middleware('permission:approval_flow.delete,true');
    Route::post('approval-flows/export', [ApprovalFlowController::class, 'export']);
    Route::post('approval-flows/import', [ApprovalFlowController::class, 'import']);
});
Route::middleware('permission:approval_delegation,true')->group(function () {
    Route::get('approval-delegations', [ApprovalDelegationController::class, 'index']);

    Route::get('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'show']);

    Route::post('approval-delegations', [ApprovalDelegationController::class, 'store'])->middleware('permission:approval_delegation.create,true');

    Route::put('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'update'])->middleware('permission:approval_delegation.edit,true');

    Route::delete('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'destroy'])->middleware('permission:approval_delegation.delete,true');
    Route::post('approval-delegations/export', [ApprovalDelegationController::class, 'export']);
    Route::post('approval-delegations/import', [ApprovalDelegationController::class, 'import']);
});
Route::get('approval-monitoring/data', [ApprovalMonitoringController::class, 'getData']);

// My Approvals
Route::get('my-approvals', [MyApprovalController::class, 'index']);
Route::post('my-approvals/{approvalRequest}/approve', [MyApprovalController::class, 'approve']);
Route::post('my-approvals/{approvalRequest}/reject', [MyApprovalController::class, 'reject']);
