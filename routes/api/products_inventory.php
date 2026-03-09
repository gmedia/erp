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
    Route::apiResource('products', ProductController::class);
    Route::post('products/export', [ProductController::class, 'export']);
    Route::post('products/import', [ProductController::class, 'import']);
});
Route::middleware('permission:product_category,true')->group(function () {
    Route::apiResource('product-categories', ProductCategoryController::class);
    Route::post('product-categories/export', [ProductCategoryController::class, 'export']);
    Route::post('product-categories/import', [ProductCategoryController::class, 'import']);
});

// Stock Transfer (with nested items)
Route::middleware('permission:stock_transfer,true')->group(function () {
    Route::apiResource('stock-transfers', StockTransferController::class);
    Route::post('stock-transfers/export', [StockTransferController::class, 'export']);
    Route::post('stock-transfers/import', [StockTransferController::class, 'import']);
    Route::get('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'getItems']);
    Route::post('stock-transfers/{stockTransfer}/items', [StockTransferItemController::class, 'syncItems']);
});

// Stock Adjustment (with nested items)
Route::middleware('permission:stock_adjustment,true')->group(function () {
    Route::apiResource('stock-adjustments', StockAdjustmentController::class);
    Route::post('stock-adjustments/export', [StockAdjustmentController::class, 'export']);
    Route::post('stock-adjustments/import', [StockAdjustmentController::class, 'import']);
    Route::get('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'getItems']);
    Route::post('stock-adjustments/{stockAdjustment}/items', [StockAdjustmentItemController::class, 'syncItems']);
});

// Inventory Stocktake (with nested items)
Route::middleware('permission:inventory_stocktake,true')->group(function () {
    Route::apiResource('inventory-stocktakes', InventoryStocktakeController::class);
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
