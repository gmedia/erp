<?php

use App\Http\Controllers\FiscalYearController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:fiscal_year,true')->group(function () {
    Route::get('fiscal-years', [FiscalYearController::class, 'index']);

    Route::get('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'show']);

    Route::post('fiscal-years', [FiscalYearController::class, 'store'])
        ->middleware('permission:fiscal_year.create,true');

    Route::put('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'update'])
        ->middleware('permission:fiscal_year.edit,true');

    Route::delete('fiscal-years/{fiscal_year}', [FiscalYearController::class, 'destroy'])
        ->middleware('permission:fiscal_year.delete,true');
    Route::post('fiscal-years/export', [FiscalYearController::class, 'export']);
    Route::post('fiscal-years/import', [FiscalYearController::class, 'import'])
        ->middleware('permission:fiscal_year.create,true');
});
