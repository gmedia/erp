<?php

use App\Http\Requests\StockAdjustments\ExportStockAdjustmentRequest;

uses()->group('stock-adjustments');

test('authorize returns true', function () {
    $request = new ExportStockAdjustmentRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules contains export filter fields', function () {
    $request = new ExportStockAdjustmentRequest();
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
    ]);
});
