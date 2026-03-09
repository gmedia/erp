<?php

use App\Http\Controllers\InventoryStocktakeController;
use App\Http\Controllers\InventoryStocktakeItemController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockAdjustmentItemController;
use App\Http\Controllers\StockMonitorController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\StockTransferItemController;
use Illuminate\Support\Facades\Route;

// --- Products & Inventory ---
Route::middleware('permission:product,true')->group(function () {
    Route::get('products', [ProductController::class, 'index']);

    Route::get('products/{product}', [ProductController::class, 'show']);

    Route::post('products', [ProductController::class, 'store'])->middleware('permission:product.create,true');

    Route::put('products/{product}', [ProductController::class, 'update'])->middleware('permission:product.edit,true');

    Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('permission:product.delete,true');
    Route::post('products/export', [ProductController::class, 'export']);
    Route::post('products/import', [ProductController::class, 'import']);
});
Route::middleware('permission:product_category,true')->group(function () {
    Route::get('product-categories', [ProductCategoryController::class, 'index']);

    Route::get('product-categories/{product_category}', [ProductCategoryController::class, 'show']);

    Route::post('product-categories', [ProductCategoryController::class, 'store'])->middleware('permission:product_category.create,true');

    Route::put('product-categories/{product_category}', [ProductCategoryController::class, 'update'])->middleware('permission:product_category.edit,true');

    Route::delete('product-categories/{product_category}', [ProductCategoryController::class, 'destroy'])->middleware('permission:product_category.delete,true');
    Route::post('product-categories/export', [ProductCategoryController::class, 'export']);
    Route::post('product-categories/import', [ProductCategoryController::class, 'import']);
});

// Stock Transfer (with nested items)
Route::middleware('permission:stock_transfer,true')->group(function () {
    Route::get('stock-transfers', [StockTransferController::class, 'index']);

    Route::get('stock-transfers/{stock_transfer}', [StockTransferController::class, 'show']);

    Route::post('stock-transfers', [StockTransferController::class, 'store'])->middleware('permission:stock_transfer.create,true');

    Route::put('stock-transfers/{stock_transfer}', [StockTransferController::class, 'update'])->middleware('permission:stock_transfer.edit,true');

    Route::delete('stock-transfers/{stock_transfer}', [StockTransferController::class, 'destroy'])->middleware('permission:stock_transfer.delete,true');
    Route::post('stock-transfers/export', [StockTransferController::class, 'export']);
    Route::post('stock-transfers/import', [StockTransferController::class, 'import']);
    Route::get('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'getItems']);
    Route::post('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'syncItems']);
});

// Stock Adjustment (with nested items)
Route::middleware('permission:stock_adjustment,true')->group(function () {
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index']);

    Route::get('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'show']);

    Route::post('stock-adjustments', [StockAdjustmentController::class, 'store'])->middleware('permission:stock_adjustment.create,true');

    Route::put('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'update'])->middleware('permission:stock_adjustment.edit,true');

    Route::delete('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'destroy'])->middleware('permission:stock_adjustment.delete,true');
    Route::post('stock-adjustments/export', [StockAdjustmentController::class, 'export']);
    Route::post('stock-adjustments/import', [StockAdjustmentController::class, 'import']);
    Route::get('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'getItems']);
    Route::post('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'syncItems']);
});

// Inventory Stocktake (with nested items)
Route::middleware('permission:inventory_stocktake,true')->group(function () {
    Route::get('inventory-stocktakes', [InventoryStocktakeController::class, 'index']);

    Route::get('inventory-stocktakes/{inventory_stocktake}', [InventoryStocktakeController::class, 'show']);

    Route::post('inventory-stocktakes', [InventoryStocktakeController::class, 'store'])->middleware('permission:inventory_stocktake.create,true');

    Route::put('inventory-stocktakes/{inventory_stocktake}', [InventoryStocktakeController::class, 'update'])->middleware('permission:inventory_stocktake.edit,true');

    Route::delete('inventory-stocktakes/{inventory_stocktake}', [InventoryStocktakeController::class, 'destroy'])->middleware('permission:inventory_stocktake.delete,true');
    Route::post('inventory-stocktakes/export', [InventoryStocktakeController::class, 'export']);
    Route::post('inventory-stocktakes/import', [InventoryStocktakeController::class, 'import']);
    Route::get('inventory-stocktakes/{inventoryStocktake}/items', [InventoryStocktakeItemController::class, 'getItems']);
    Route::post('inventory-stocktakes/{inventoryStocktake}/items', [InventoryStocktakeItemController::class, 'syncItems']);
});

// Stock Monitor & Movement (read-only)
Route::middleware('permission:stock_monitor,true')->group(function () {
    Route::get('stock-monitor', [StockMonitorController::class, 'index']);
    Route::post('stock-monitor/export', [StockMonitorController::class, 'export']);
});
Route::middleware('permission:stock_movement,true')->group(function () {
    Route::get('stock-movements', [StockMovementController::class, 'index']);
    Route::post('stock-movements/export', [StockMovementController::class, 'export']);
});
