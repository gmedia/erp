<?php

use App\Http\Controllers\InventoryStocktakeController;
use App\Http\Controllers\InventoryStocktakeItemController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:inventory_stocktake,true')->group(function () {
    Route::get('inventory-stocktakes', [InventoryStocktakeController::class, 'index']);

    Route::get('inventory-stocktakes/{inventory_stocktake}', [InventoryStocktakeController::class, 'show']);

    Route::post('inventory-stocktakes', [InventoryStocktakeController::class, 'store'])
        ->middleware('permission:inventory_stocktake.create,true');

    Route::put('inventory-stocktakes/{inventory_stocktake}', [InventoryStocktakeController::class, 'update'])
        ->middleware('permission:inventory_stocktake.edit,true');

    Route::delete('inventory-stocktakes/{inventory_stocktake}', [InventoryStocktakeController::class, 'destroy'])
        ->middleware('permission:inventory_stocktake.delete,true');
    Route::post('inventory-stocktakes/export', [InventoryStocktakeController::class, 'export']);
    Route::post('inventory-stocktakes/import', [InventoryStocktakeController::class, 'import'])
        ->middleware('permission:inventory_stocktake.create,true');
    Route::get(
        'inventory-stocktakes/{inventoryStocktake}/items',
        [InventoryStocktakeItemController::class, 'getItems']
    );
    Route::post(
        'inventory-stocktakes/{inventoryStocktake}/items',
        [InventoryStocktakeItemController::class, 'syncItems']
    );
});
