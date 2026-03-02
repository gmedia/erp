<?php

use App\Http\Controllers\Admin\AdminSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('admin-settings', [AdminSettingController::class, 'index'])
        ->name('admin-settings.index')
        ->middleware('permission:admin_setting');

    Route::put('admin-settings', [AdminSettingController::class, 'update'])
        ->name('admin-settings.update')
        ->middleware('permission:admin_setting.edit,true');
});
