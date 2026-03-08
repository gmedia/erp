<?php

use App\Http\Controllers\CoaVersionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::middleware('permission:coa_version,true')->group(function () {
        Route::get('coa-versions', [CoaVersionController::class, 'index']);
        Route::get('coa-versions/{coa_version}', [CoaVersionController::class, 'show']);
        Route::post('coa-versions', [CoaVersionController::class, 'store'])->middleware('permission:coa_version.create,true');
        Route::put('coa-versions/{coa_version}', [CoaVersionController::class, 'update'])->middleware('permission:coa_version.edit,true');
        Route::delete('coa-versions/{coa_version}', [CoaVersionController::class, 'destroy'])->middleware('permission:coa_version.delete,true');
        Route::post('coa-versions/export', [CoaVersionController::class, 'export']);
    });
});
