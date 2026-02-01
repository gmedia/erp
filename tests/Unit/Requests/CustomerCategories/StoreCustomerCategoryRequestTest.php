<?php

use App\Http\Requests\CustomerCategories\StoreCustomerCategoryRequest;

uses()->group('customer-categories', 'requests');

test('authorize returns true', function () {
    $request = new StoreCustomerCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreCustomerCategoryRequest();
    
    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:customer_categories,name'],
    ]);
});
