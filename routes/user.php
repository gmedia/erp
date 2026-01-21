<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users')->middleware('permission:user');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:user,true')->group(function () {
        Route::get('employees/{employee}/user', [UserController::class, 'getUserByEmployee']);
        Route::post('employees/{employee}/user', [UserController::class, 'updateUser']);
    });
});

