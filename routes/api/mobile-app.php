<?php

use App\Http\Controllers\MobileAppController;
use App\Http\Controllers\MobileAppReleaseController;
use Illuminate\Support\Facades\Route;

Route::get('mobile-app/latest', [MobileAppController::class, 'latest'])
    ->middleware('throttle:api');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('mobile-app', [MobileAppController::class, 'show']);
    Route::get('mobile-app/download', [MobileAppController::class, 'download']);

    Route::middleware('permission:mobile_app_release')->group(function () {
        Route::get('mobile-app-releases', [MobileAppReleaseController::class, 'index']);
        Route::post('mobile-app-releases', [MobileAppReleaseController::class, 'store']);
        Route::patch('mobile-app-releases/{mobileAppRelease}/activate', [
            MobileAppReleaseController::class,
            'activate',
        ]);
        Route::patch('mobile-app-releases/{mobileAppRelease}/deactivate', [
            MobileAppReleaseController::class,
            'deactivate',
        ]);
    });
});
