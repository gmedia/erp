<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('employees', function () {
        return Inertia::render('employees/index');
    })->name('employees')->middleware('permission:employee');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:employee,true')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::get('employees/{employee}', [EmployeeController::class, 'show']);
        Route::post('employees', [EmployeeController::class, 'store'])->middleware('permission:employee.create,true');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employee.edit,true');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('permission:employee.delete,true');
        Route::post('employees/export', [EmployeeController::class, 'export']);
    });
});
