<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PositionController;
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
    })->name('employees');

    Route::get('positions', function () {
        return Inertia::render('positions/index');
    })->name('positions');

    // Departments page route
    Route::get('departments', function () {
        return Inertia::render('departments/index');
    })->name('departments');

    Route::get('permissions', [\App\Http\Controllers\PermissionController::class, 'index'])->name('permissions.index');
});

// API routes for employee CRUD operations
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::apiResource('employees', EmployeeController::class);
    Route::post('employees/export', [EmployeeController::class, 'export']);
    Route::apiResource('positions', PositionController::class);
    Route::post('positions/export', [PositionController::class, 'export']);
    Route::apiResource('departments', DepartmentController::class);
    Route::post('departments/export', [DepartmentController::class, 'export']);

    Route::get('employees/{employee}/permissions', [EmployeeController::class, 'permissions']);
    Route::post('employees/{employee}/permissions', [EmployeeController::class, 'syncPermissions']);
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
