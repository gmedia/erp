<?php

use App\Http\Requests\StockAdjustments\StoreStockAdjustmentRequest;

uses()->group('stock-adjustments');

test('authorize returns true', function () {
    $request = new StoreStockAdjustmentRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules contains required fields and item rules', function () {
    $request = new StoreStockAdjustmentRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'warehouse_id',
        'adjustment_date',
        'adjustment_type',
        'status',
        'items',
        'items.*.product_id',
        'items.*.unit_id',
        'items.*.quantity_adjusted',
    ]);
});
