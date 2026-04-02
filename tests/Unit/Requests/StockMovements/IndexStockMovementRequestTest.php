<?php

use App\Http\Requests\StockMovements\IndexStockMovementRequest;

uses()->group('stock-movements');

test('authorize returns true for stock movement index request', function () {
    $request = new IndexStockMovementRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules contains stock movement filter and pagination fields', function () {
    $request = new IndexStockMovementRequest;
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
        'per_page',
        'export',
    ]);
});
