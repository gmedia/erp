<?php

use App\Http\Controllers\AccountMappingController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:account_mapping,true')->group(function () {
    Route::get('account-mappings', [AccountMappingController::class, 'index']);

    Route::get('account-mappings/{account_mapping}', [AccountMappingController::class, 'show']);

    Route::post('account-mappings', [AccountMappingController::class, 'store'])->middleware('permission:account_mapping.create,true');

    Route::put('account-mappings/{account_mapping}', [AccountMappingController::class, 'update'])->middleware('permission:account_mapping.edit,true');

    Route::delete('account-mappings/{account_mapping}', [AccountMappingController::class, 'destroy'])->middleware('permission:account_mapping.delete,true');
    Route::post('account-mappings/export', [AccountMappingController::class, 'export']);
    Route::post('account-mappings/import', [AccountMappingController::class, 'import']);
});
