<?php

use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('departments', function () {
        return Inertia::render('departments/index');
    })->name('departments')->middleware('permission:department');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:department,true')->group(function () {
        Route::get('departments', [DepartmentController::class, 'index']);
        Route::get('departments/{department}', [DepartmentController::class, 'show']);
        Route::post('departments', [DepartmentController::class, 'store'])->middleware('permission:department.create,true');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->middleware('permission:department.edit,true');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->middleware('permission:department.delete,true');
        Route::post('departments/export', [DepartmentController::class, 'export']);
    });
});
