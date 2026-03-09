<?php

use App\Http\Requests\InventoryStocktakes\ExportInventoryStocktakeRequest;

uses()->group('inventory-stocktakes');

test('authorize returns true', function () {
    $request = new ExportInventoryStocktakeRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules contains export filter keys', function () {
    $request = new ExportInventoryStocktakeRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'search',
        'warehouse_id',
        'product_category_id',
        'status',
        'sort_by',
        'sort_direction',
    ]);
});

test('rules validation passes with product_category_id sort_by', function () {
    $data = ['sort_by' => 'product_category_id'];

    $validator = validator($data, (new ExportInventoryStocktakeRequest)->rules());

    expect(! $validator->fails())->toBeTrue();
});
