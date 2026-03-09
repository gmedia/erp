<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// --- Dashboard ---
Route::get('dashboard', [DashboardController::class, 'index']);
