<?php

use App\Http\Controllers\BranchController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:branch,true')->group(function () {
    Route::get('branches', [BranchController::class, 'index']);

    Route::get('branches/{branch}', [BranchController::class, 'show']);

    Route::post('branches', [BranchController::class, 'store'])->middleware('permission:branch.create,true');

    Route::put('branches/{branch}', [BranchController::class, 'update'])->middleware('permission:branch.edit,true');

    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])->middleware('permission:branch.delete,true');
    Route::post('branches/export', [BranchController::class, 'export']);
    Route::post('branches/import', [BranchController::class, 'import'])->middleware('permission:branch.create,true');
});
