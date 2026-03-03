<?php

use App\Http\Requests\StockAdjustments\UpdateStockAdjustmentItemsRequest;

uses()->group('stock-adjustments');

test('authorize returns true', function () {
    $request = new UpdateStockAdjustmentItemsRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules contains items rules', function () {
    $request = new UpdateStockAdjustmentItemsRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'items',
        'items.*.product_id',
        'items.*.unit_id',
        'items.*.quantity_adjusted',
    ]);
});
