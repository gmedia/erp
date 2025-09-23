<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('employees', function () {
        return Inertia::render('employees/index');
    })->name('employees');
});

// API routes for employee CRUD operations
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::apiResource('employees', EmployeeController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
