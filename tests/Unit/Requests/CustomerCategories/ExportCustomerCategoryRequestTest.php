<?php

use App\Http\Requests\CustomerCategories\ExportCustomerCategoryRequest;

uses()->group('customer-categories');

test('authorize returns true', function () {
    $request = new ExportCustomerCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportCustomerCategoryRequest();

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'sort_by' => ['nullable', 'string', 'in:id,name,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
    ]);
});
