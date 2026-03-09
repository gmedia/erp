<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:user,true')->group(function () {
    Route::get('users', [UserController::class, 'apiIndex']);
    Route::get('users/{user}', [UserController::class, 'apiShow']);
});
