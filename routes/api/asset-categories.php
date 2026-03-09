<?php

use App\Http\Controllers\AssetCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:asset_category,true')->group(function () {
    Route::get('asset-categories', [AssetCategoryController::class, 'index']);

    Route::get('asset-categories/{asset_category}', [AssetCategoryController::class, 'show']);

    Route::post('asset-categories', [AssetCategoryController::class, 'store'])->middleware('permission:asset_category.create,true');

    Route::put('asset-categories/{asset_category}', [AssetCategoryController::class, 'update'])->middleware('permission:asset_category.edit,true');

    Route::delete('asset-categories/{asset_category}', [AssetCategoryController::class, 'destroy'])->middleware('permission:asset_category.delete,true');
    Route::post('asset-categories/export', [AssetCategoryController::class, 'export']);
    Route::post('asset-categories/import', [AssetCategoryController::class, 'import']);
});
