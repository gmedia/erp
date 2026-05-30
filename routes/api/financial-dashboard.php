<?php

use App\Http\Controllers\FinancialDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('financial-dashboard', FinancialDashboardController::class);
