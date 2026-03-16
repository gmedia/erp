<?php

use App\Http\Controllers\ApprovalFlowController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:approval_flow,true')->group(function () {
    Route::get('approval-flows', [ApprovalFlowController::class, 'index']);

    Route::get('approval-flows/{approval_flow}', [ApprovalFlowController::class, 'show']);

    Route::post('approval-flows', [ApprovalFlowController::class, 'store'])
        ->middleware('permission:approval_flow.create,true');

    Route::put('approval-flows/{approval_flow}', [ApprovalFlowController::class, 'update'])
        ->middleware('permission:approval_flow.edit,true');

    Route::delete('approval-flows/{approval_flow}', [ApprovalFlowController::class, 'destroy'])
        ->middleware('permission:approval_flow.delete,true');
    Route::post('approval-flows/export', [ApprovalFlowController::class, 'export']);
    Route::post('approval-flows/import', [ApprovalFlowController::class, 'import'])
        ->middleware('permission:approval_flow.create,true');
});
