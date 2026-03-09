<?php

use App\Http\Requests\Warehouses\IndexWarehouseRequest;

uses()->group('warehouses');

test('authorize returns true', function () {
    $request = new IndexWarehouseRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexWarehouseRequest;

    $rules = $request->rules();
    expect($rules)->toHaveKeys([
        'search',
        'sort_by',
        'sort_direction',
        'per_page',
        'page',
        'branch_id',
    ]);

    expect(implode(',', $rules['sort_by']))->toContain('branch');
});
