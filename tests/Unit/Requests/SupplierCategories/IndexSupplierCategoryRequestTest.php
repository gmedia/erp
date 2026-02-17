<?php

use App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest;

uses()->group('supplier-categories');

test('authorize returns true', function () {
    $request = new IndexSupplierCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexSupplierCategoryRequest();

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'sort_by' => ['nullable', 'string', 'in:id,name,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
