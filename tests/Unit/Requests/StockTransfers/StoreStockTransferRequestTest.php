<?php

use App\Http\Requests\StockTransfers\StoreStockTransferRequest;

uses()->group('stock-transfers');

test('authorize returns true', function () {
    $request = new StoreStockTransferRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules contains required fields and item rules', function () {
    $request = new StoreStockTransferRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'from_warehouse_id',
        'to_warehouse_id',
        'transfer_date',
        'status',
        'items',
        'items.*.product_id',
        'items.*.unit_id',
        'items.*.quantity',
    ]);
});
