<?php

use App\Http\Requests\ProductCategories\StoreProductCategoryRequest;

uses()->group('product-categories', 'requests');

test('authorize returns true', function () {
    $request = new StoreProductCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreProductCategoryRequest();
    
    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:product_categories,name'],
        'description' => ['nullable', 'string'],
    ]);
});
