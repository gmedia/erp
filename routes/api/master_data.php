<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// --- Master Data ---
Route::middleware('permission:department,true')->group(function () {
    Route::apiResource('departments', DepartmentController::class);
    Route::post('departments/export', [DepartmentController::class, 'export']);
    Route::post('departments/import', [DepartmentController::class, 'import']);
});
Route::middleware('permission:position,true')->group(function () {
    Route::apiResource('positions', PositionController::class);
    Route::post('positions/export', [PositionController::class, 'export']);
    Route::post('positions/import', [PositionController::class, 'import']);
});
Route::middleware('permission:branch,true')->group(function () {
    Route::apiResource('branches', BranchController::class);
    Route::post('branches/export', [BranchController::class, 'export']);
    Route::post('branches/import', [BranchController::class, 'import']);
});
Route::middleware('permission:employee,true')->group(function () {
    Route::apiResource('employees', EmployeeController::class);
    Route::post('employees/export', [EmployeeController::class, 'export']);
    Route::post('employees/import', [EmployeeController::class, 'import']);
});
Route::middleware('permission:unit,true')->group(function () {
    Route::apiResource('units', UnitController::class);
    Route::post('units/export', [UnitController::class, 'export']);
    Route::post('units/import', [UnitController::class, 'import']);
});
Route::middleware('permission:warehouse,true')->group(function () {
    Route::apiResource('warehouses', \App\Http\Controllers\WarehouseController::class);
    Route::post('warehouses/export', [\App\Http\Controllers\WarehouseController::class, 'export']);
    Route::post('warehouses/import', [\App\Http\Controllers\WarehouseController::class, 'import']);
});
