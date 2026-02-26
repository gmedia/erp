<?php

use App\Http\Requests\InventoryStocktakes\UpdateInventoryStocktakeRequest;

uses()->group('inventory-stocktakes');

test('authorize returns true', function () {
    $request = new UpdateInventoryStocktakeRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules contains updatable fields and optional item rules', function () {
    $request = new UpdateInventoryStocktakeRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'stocktake_number',
        'warehouse_id',
        'stocktake_date',
        'status',
        'items',
        'items.*.product_id',
        'items.*.unit_id',
        'items.*.system_quantity',
    ]);
});

