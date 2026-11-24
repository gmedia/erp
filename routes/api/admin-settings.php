<?php

use App\Http\Controllers\Admin\AdminSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:admin_setting,true')->group(function () {
    Route::get('admin-settings', [AdminSettingController::class, 'index']);
    Route::post('admin-settings', [AdminSettingController::class, 'update'])
        ->middleware('permission:admin_setting.edit,true');
    Route::put('admin-settings', [AdminSettingController::class, 'update'])
        ->middleware('permission:admin_setting.edit,true');
    Route::post('admin-settings/test-smtp', [AdminSettingController::class, 'testSmtp'])
        ->middleware('permission:admin_setting.edit,true');
});
