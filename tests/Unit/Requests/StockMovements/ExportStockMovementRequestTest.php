<?php

use App\Http\Requests\StockMovements\ExportStockMovementRequest;

uses()->group('stock-movements');

test('authorize returns true for stock movement export request', function () {
    $request = new ExportStockMovementRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules contains export stock movement fields', function () {
    $request = new ExportStockMovementRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'search',
        'product_id',
        'warehouse_id',
        'movement_type',
        'start_date',
        'end_date',
        'sort_by',
        'sort_direction',
        'format',
    ]);
});
