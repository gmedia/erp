<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Locale switching route
Route::post('locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('employees', function () {
        return Inertia::render('employees/index');
    })->name('employees')->middleware('permission:employee');

    Route::get('positions', function () {
        return Inertia::render('positions/index');
    })->name('positions')->middleware('permission:position');

    Route::get('departments', function () {
        return Inertia::render('departments/index');
    })->name('departments')->middleware('permission:department');

    Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index')->middleware('permission:permission');

    Route::get('users', [UserController::class, 'index'])->name('users')->middleware('permission:user');
});

// API routes for employee CRUD operations
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:employee,true')->group(function () {
        Route::get('employees', [EmployeeController::class, 'index']);
        Route::get('employees/{employee}', [EmployeeController::class, 'show']);
        Route::post('employees', [EmployeeController::class, 'store'])->middleware('permission:employee.create,true');
        Route::put('employees/{employee}', [EmployeeController::class, 'update'])->middleware('permission:employee.edit,true');
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->middleware('permission:employee.delete,true');
        Route::post('employees/export', [EmployeeController::class, 'export']);
    }); 

    Route::middleware('permission:position,true')->group(function () {
        Route::get('positions', [PositionController::class, 'index']);
        Route::get('positions/{position}', [PositionController::class, 'show']);
        Route::post('positions', [PositionController::class, 'store'])->middleware('permission:position.create,true');
        Route::put('positions/{position}', [PositionController::class, 'update'])->middleware('permission:position.edit,true');
        Route::delete('positions/{position}', [PositionController::class, 'destroy'])->middleware('permission:position.delete,true');
        Route::post('positions/export', [PositionController::class, 'export']);
    });

    Route::middleware('permission:department,true')->group(function () {
        Route::get('departments', [DepartmentController::class, 'index']);
        Route::get('departments/{department}', [DepartmentController::class, 'show']);
        Route::post('departments', [DepartmentController::class, 'store'])->middleware('permission:department.create,true');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:department.edit,true');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:department.delete,true');
        Route::post('departments/export', [DepartmentController::class, 'export']);
    });

    Route::middleware('permission:permission,true')->group(function () {
        Route::get('employees/{employee}/permissions', [EmployeeController::class, 'permissions']);
        Route::post('employees/{employee}/permissions', [EmployeeController::class, 'syncPermissions']);
    });

    Route::middleware('permission:user,true')->group(function () {
        Route::get('employees/{employee}/user', [UserController::class, 'getUserByEmployee']);
        Route::post('employees/{employee}/user', [UserController::class, 'updateUser']);
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
