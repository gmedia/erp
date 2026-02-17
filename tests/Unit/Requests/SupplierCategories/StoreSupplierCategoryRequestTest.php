<?php

use App\Http\Requests\SupplierCategories\StoreSupplierCategoryRequest;

uses()->group('supplier-categories');

test('authorize returns true', function () {
    $request = new StoreSupplierCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreSupplierCategoryRequest();
    
    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:supplier_categories,name'],
    ]);
});
