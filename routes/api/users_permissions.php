<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// --- Users & Permissions ---
Route::get('users', [UserController::class, 'apiIndex']);
Route::get('users/{user}', [UserController::class, 'apiShow']);
Route::group(['middleware' => ['permission:permission']], function () {
    Route::get('permissions', [\App\Http\Controllers\PermissionController::class, 'index']);
    Route::get('employees/{employee}/permissions', [EmployeeController::class, 'permissions']);
    Route::post('employees/{employee}/permissions', [EmployeeController::class, 'syncPermissions']);
});

// Pipeline Audit Trail
Route::get('pipeline-audit-trail', [\App\Http\Controllers\PipelineAuditTrailController::class, 'index'])
    ->middleware('permission:pipeline_audit_trail');
Route::post('pipeline-audit-trail/export', [\App\Http\Controllers\PipelineAuditTrailController::class, 'export'])
    ->middleware('permission:pipeline_audit_trail');

// Approval Audit Trail
Route::get('approval-audit-trail', [\App\Http\Controllers\ApprovalAuditTrailController::class, 'index'])
    ->middleware('permission:approval_audit_trail');
Route::post('approval-audit-trail/export', [\App\Http\Controllers\ApprovalAuditTrailController::class, 'export'])
    ->middleware('permission:approval_audit_trail.export');

// Employee User Management
Route::group(['middleware' => ['permission:user']], function () {
    Route::get('employees/{employee}/user', [\App\Http\Controllers\UserController::class, 'getUserByEmployee']);
    Route::post('employees/{employee}/user', [\App\Http\Controllers\UserController::class, 'updateUser']);
});
