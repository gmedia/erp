<?php

use App\Http\Controllers\ApprovalDelegationController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:approval_delegation,true')->group(function () {
    Route::get('approval-delegations', [ApprovalDelegationController::class, 'index']);

    Route::get('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'show']);

    Route::post('approval-delegations', [ApprovalDelegationController::class, 'store'])
        ->middleware('permission:approval_delegation.create,true');

    Route::put('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'update'])
        ->middleware('permission:approval_delegation.edit,true');

    Route::delete('approval-delegations/{approval_delegation}', [ApprovalDelegationController::class, 'destroy'])
        ->middleware('permission:approval_delegation.delete,true');
    Route::post('approval-delegations/export', [ApprovalDelegationController::class, 'export']);
    Route::post('approval-delegations/import', [ApprovalDelegationController::class, 'import'])
        ->middleware('permission:approval_delegation.create,true');
});
