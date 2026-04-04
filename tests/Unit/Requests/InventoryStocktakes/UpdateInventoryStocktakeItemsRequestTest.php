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

    expect($rules['items'])->toContain('required', 'array', 'min:1')
        ->and($rules['items.*.product_id'])->toContain('required', 'exists:products,id')
        ->and($rules['items.*.unit_id'])->toContain('required', 'exists:units,id');
});
