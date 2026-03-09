<?php

use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:unit,true')->group(function () {
    Route::get('units', [UnitController::class, 'index']);

    Route::get('units/{unit}', [UnitController::class, 'show']);

    Route::post('units', [UnitController::class, 'store'])->middleware('permission:unit.create,true');

    Route::put('units/{unit}', [UnitController::class, 'update'])->middleware('permission:unit.edit,true');

    Route::delete('units/{unit}', [UnitController::class, 'destroy'])->middleware('permission:unit.delete,true');
    Route::post('units/export', [UnitController::class, 'export']);
    Route::post('units/import', [UnitController::class, 'import'])->middleware('permission:unit.create,true');
});
