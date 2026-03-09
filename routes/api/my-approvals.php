<?php

use App\Http\Controllers\MyApprovalController;
use Illuminate\Support\Facades\Route;

Route::get('my-approvals', [MyApprovalController::class, 'index']);

Route::post('my-approvals/{approvalRequest}/approve', [MyApprovalController::class, 'approve']);

Route::post('my-approvals/{approvalRequest}/reject', [MyApprovalController::class, 'reject']);
