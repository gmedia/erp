<?php

use App\Http\Controllers\PositionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('positions', function () {
        return Inertia::render('positions/index');
    })->name('positions')->middleware('permission:position');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:position,true')->group(function () {
        Route::get('positions', [PositionController::class, 'index']);
        Route::get('positions/{position}', [PositionController::class, 'show']);
        Route::post('positions', [PositionController::class, 'store'])->middleware('permission:position.create,true');
        Route::put('positions/{position}', [PositionController::class, 'update'])->middleware('permission:position.edit,true');
        Route::delete('positions/{position}', [PositionController::class, 'destroy'])->middleware('permission:position.delete,true');
        Route::post('positions/export', [PositionController::class, 'export']);
    });
});
