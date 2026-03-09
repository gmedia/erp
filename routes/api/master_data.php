<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

// --- Master Data ---
Route::middleware('permission:department,true')->group(function () {
    Route::get('departments', [DepartmentController::class, 'index']);

    Route::get('departments/{department}', [DepartmentController::class, 'show']);

    Route::post('departments', [DepartmentController::class, 'store'])->middleware('permission:department.create,true');

    Route::put('departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:department.edit,true');

    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:department.delete,true');
    Route::post('departments/export', [DepartmentController::class, 'export']);
    Route::post('departments/import', [DepartmentController::class, 'import']);
});
Route::middleware('permission:position,true')->group(function () {
    Route::get('positions', [PositionController::class, 'index']);

    Route::get('positions/{position}', [PositionController::class, 'show']);

    Route::post('positions', [PositionController::class, 'store'])->middleware('permission:position.create,true');

    Route::put('positions/{position}', [PositionController::class, 'update'])->middleware('permission:position.edit,true');

    Route::delete('positions/{position}', [PositionController::class, 'destroy'])->middleware('permission:position.delete,true');
    Route::post('positions/export', [PositionController::class, 'export']);
    Route::post('positions/import', [PositionController::class, 'import']);
});
Route::middleware('permission:branch,true')->group(function () {
    Route::get('branches', [BranchController::class, 'index']);

    Route::get('branches/{branch}', [BranchController::class, 'show']);

    Route::post('branches', [BranchController::class, 'store'])->middleware('permission:branch.create,true');

    Route::put('branches/{branch}', [BranchController::class, 'update'])->middleware('permission:branch.edit,true');

    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('permission:branch.delete,true');
    Route::post('branches/export', [BranchController::class, 'export']);
    Route::post('branches/import', [BranchController::class, 'import']);
});
Route::middleware('permission:employee,true')->group(function () {
    Route::get('employees', [EmployeeController::class, 'index']);

    Route::get('employees/{employee}', [EmployeeController::class, 'show']);

    Route::post('employees', [EmployeeController::class, 'store'])->middleware('permission:employee.create,true');

    Route::put('employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employee.edit,true');

    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('permission:employee.delete,true');
    Route::post('employees/export', [EmployeeController::class, 'export']);
    Route::post('employees/import', [EmployeeController::class, 'import']);
});
Route::middleware('permission:unit,true')->group(function () {
    Route::get('units', [UnitController::class, 'index']);

    Route::get('units/{unit}', [UnitController::class, 'show']);

    Route::post('units', [UnitController::class, 'store'])->middleware('permission:unit.create,true');

    Route::put('units/{unit}', [UnitController::class, 'update'])->middleware('permission:unit.edit,true');

    Route::delete('units/{unit}', [UnitController::class, 'destroy'])->middleware('permission:unit.delete,true');
    Route::post('units/export', [UnitController::class, 'export']);
    Route::post('units/import', [UnitController::class, 'import']);
});
Route::middleware('permission:warehouse,true')->group(function () {
    Route::get('warehouses', [\App\Http\Controllers\WarehouseController::class, 'index']);

    Route::get('warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'show']);

    Route::post('warehouses', [\App\Http\Controllers\WarehouseController::class, 'store'])->middleware('permission:warehouse.create,true');

    Route::put('warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'update'])->middleware('permission:warehouse.edit,true');

    Route::delete('warehouses/{warehouse}', [\App\Http\Controllers\WarehouseController::class, 'destroy'])->middleware('permission:warehouse.delete,true');
    Route::post('warehouses/export', [\App\Http\Controllers\WarehouseController::class, 'export']);
    Route::post('warehouses/import', [\App\Http\Controllers\WarehouseController::class, 'import']);
});
