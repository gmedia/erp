<?php

use App\Http\Controllers\ApprovalDelegationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:approval_delegation,true')->group(function () {
        Route::get('approval-delegations', [ApprovalDelegationController::class, 'index']);
        Route::post('approval-delegations/export', [ApprovalDelegationController::class, 'export'])->middleware('permission:approval_delegation.export,true');
        Route::get('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'show']);
        Route::post('approval-delegations', [ApprovalDelegationController::class, 'store'])->middleware('permission:approval_delegation.create,true');
        Route::put('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'update'])->middleware('permission:approval_delegation.edit,true');
        Route::delete('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'destroy'])->middleware('permission:approval_delegation.delete,true');
    });
});
