<?php

use App\Http\Controllers\AgingDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:aging_dashboard,true')->group(function () {
    Route::get('aging-dashboard', AgingDashboardController::class);
});
