<?php

use App\Http\Requests\StockAdjustments\UpdateStockAdjustmentRequest;

uses()->group('stock-adjustments');

test('authorize returns true', function () {
    $request = new UpdateStockAdjustmentRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules contains optional update fields', function () {
    $request = new UpdateStockAdjustmentRequest();
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
