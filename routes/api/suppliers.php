<?php

use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:supplier,true')->group(function () {
    Route::get('suppliers', [SupplierController::class, 'index']);

    Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);

    Route::post('suppliers', [SupplierController::class, 'store'])->middleware('permission:supplier.create,true');

    Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:supplier.edit,true');

    Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:supplier.delete,true');
    Route::post('suppliers/export', [SupplierController::class, 'export']);
    Route::post('suppliers/import', [SupplierController::class, 'import']);
});
