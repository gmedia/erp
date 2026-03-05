<?php

use App\Http\Requests\StockAdjustments\IndexStockAdjustmentRequest;

uses()->group('stock-adjustments');

test('authorize returns true', function () {
    $request = new IndexStockAdjustmentRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules contains filter and pagination fields', function () {
    $request = new IndexStockAdjustmentRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'search',
        'warehouse_id',
        'status',
        'adjustment_type',
        'inventory_stocktake_id',
        'adjustment_date_from',
        'adjustment_date_to',
        'sort_by',
        'sort_direction',
        'per_page',
        'page',
    ]);
});
