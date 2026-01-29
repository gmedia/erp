<?php

use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Frontend route
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('units', function () {
        return Inertia::render('units/index');
    })->name('units')->middleware('permission:unit');
});

// API routes
Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:unit,true')->group(function () {
        Route::get('units', [UnitController::class, 'index']);
        Route::get('units/{unit}', [UnitController::class, 'show']);
        Route::post('units', [UnitController::class, 'store'])
            ->middleware('permission:unit.create,true');
        Route::put('units/{unit}', [UnitController::class, 'update'])
            ->middleware('permission:unit.edit,true');
        Route::delete('units/{unit}', [UnitController::class, 'destroy'])
            ->middleware('permission:unit.delete,true');
        Route::post('units/export', [UnitController::class, 'export']);
    });
});
