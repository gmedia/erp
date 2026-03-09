<?php

use App\Http\Requests\InventoryStocktakes\StoreInventoryStocktakeRequest;

uses()->group('inventory-stocktakes');

test('authorize returns true', function () {
    $request = new StoreInventoryStocktakeRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules contains required fields and item rules', function () {
    $request = new StoreInventoryStocktakeRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'warehouse_id',
        'stocktake_date',
        'status',
        'items',
        'items.*.product_id',
        'items.*.unit_id',
        'items.*.system_quantity',
    ]);
});
