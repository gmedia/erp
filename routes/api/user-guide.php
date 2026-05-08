<?php

use App\Http\Controllers\UserGuideController;
use Illuminate\Support\Facades\Route;

Route::get('user-guide', [UserGuideController::class, 'index']);
Route::get('user-guide/{slug}', [UserGuideController::class, 'show']);
