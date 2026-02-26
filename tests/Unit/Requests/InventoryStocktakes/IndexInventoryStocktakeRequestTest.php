<?php

use App\Http\Requests\InventoryStocktakes\IndexInventoryStocktakeRequest;

uses()->group('inventory-stocktakes');

test('authorize returns true', function () {
    $request = new IndexInventoryStocktakeRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules contains filter and sorting keys', function () {
    $request = new IndexInventoryStocktakeRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'search',
        'warehouse_id',
        'product_category_id',
        'status',
        'sort_by',
        'sort_direction',
        'per_page',
        'page',
    ]);
});

