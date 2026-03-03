<?php

use App\Http\Controllers\MyApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('my-approvals', [MyApprovalController::class, 'index'])->name('my-approvals.index');
    Route::post('my-approvals/{approvalRequest}/approve', [MyApprovalController::class, 'approve'])->name('my-approvals.approve');
    Route::post('my-approvals/{approvalRequest}/reject', [MyApprovalController::class, 'reject'])->name('my-approvals.reject');
});
