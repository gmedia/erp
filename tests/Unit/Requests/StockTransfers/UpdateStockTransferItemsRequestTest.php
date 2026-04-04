<?php

use App\Http\Requests\StockTransfers\UpdateStockTransferItemsRequest;

uses()->group('stock-transfers');

test('authorize returns true', function () {
    $request = new UpdateStockTransferItemsRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules contains item array validation', function () {
    $request = new UpdateStockTransferItemsRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'items',
        'items.*.product_id',
        'items.*.unit_id',
        'items.*.quantity',
    ]);

    expect($rules['items'])->toContain('required', 'array', 'min:1')
        ->and($rules['items.*.product_id'])->toContain('required', 'exists:products,id')
        ->and($rules['items.*.unit_id'])->toContain('required', 'exists:units,id');
});
