<?php

use App\Http\Controllers\FinancialDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:financial_dashboard,true')->group(function () {
    Route::get('financial-dashboard', FinancialDashboardController::class);
});
