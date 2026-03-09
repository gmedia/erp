<?php

use App\Http\Requests\InventoryStocktakes\UpdateInventoryStocktakeItemsRequest;

uses()->group('inventory-stocktakes');

test('authorize returns true', function () {
    $request = new UpdateInventoryStocktakeItemsRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules contains item array rules', function () {
    $request = new UpdateInventoryStocktakeItemsRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'items',
        'items.*.product_id',
        'items.*.unit_id',
        'items.*.system_quantity',
    ]);
});
