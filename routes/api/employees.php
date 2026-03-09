<?php

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:employee,true')->group(function () {
    Route::get('employees', [EmployeeController::class, 'index']);

    Route::get('employees/{employee}', [EmployeeController::class, 'show']);

    Route::post('employees', [EmployeeController::class, 'store'])->middleware('permission:employee.create,true');

    Route::put('employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employee.edit,true');

    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('permission:employee.delete,true');
    Route::post('employees/export', [EmployeeController::class, 'export']);
    Route::post('employees/import', [EmployeeController::class, 'import']);
});

Route::middleware('permission:user,true')->group(function () {
    Route::get('employees/{employee}/permissions', [EmployeeController::class, 'permissions']);
    Route::post('employees/{employee}/permissions', [EmployeeController::class, 'syncPermissions']);
    Route::get('employees/{employee}/user', [\App\Http\Controllers\UserController::class, 'getUserByEmployee']);
    Route::post('employees/{employee}/user', [\App\Http\Controllers\UserController::class, 'updateUser']);
});
