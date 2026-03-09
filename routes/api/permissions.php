<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:permission,true')->group(function () {
    Route::get('permissions', [PermissionController::class, 'index']);
});
