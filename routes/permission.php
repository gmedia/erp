<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index')->middleware('permission:permission');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:permission,true')->group(function () {
        Route::get('employees/{employee}/permissions', [EmployeeController::class, 'permissions']);
        Route::post('employees/{employee}/permissions', [EmployeeController::class, 'syncPermissions']);
    });
});
