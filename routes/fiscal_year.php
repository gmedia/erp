<?php

use App\Http\Controllers\FiscalYearController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('fiscal-years', function () {
        return Inertia::render('fiscal-years/index');
    })->name('fiscal-years')->middleware('permission:fiscal-year');
});

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:fiscal-year,true')->group(function () {
        Route::get('fiscal-years', [FiscalYearController::class, 'index']);
        Route::get('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'show']);
        Route::post('fiscal-years', [FiscalYearController::class, 'store'])->middleware('permission:fiscal-year.create,true');
        Route::put('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'update'])->middleware('permission:fiscal-year.edit,true');
        Route::delete('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'destroy'])->middleware('permission:fiscal-year.delete,true');
        Route::post('fiscal-years/export', [FiscalYearController::class, 'export']);
    });
});
