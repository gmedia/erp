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

test('rules validation passes with product_category_id sort_by', function () {
    $data = ['sort_by' => 'product_category_id'];

    $validator = validator($data, (new IndexInventoryStocktakeRequest())->rules());

    expect(!$validator->fails())->toBeTrue();
});
