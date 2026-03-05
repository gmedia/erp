<?php

use App\Http\Requests\Warehouses\ExportWarehouseRequest;

uses()->group('warehouses');

test('authorize returns true', function () {
    $request = new ExportWarehouseRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportWarehouseRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys(['search', 'sort_by', 'sort_direction', 'branch_id']);
    expect(implode(',', $rules['sort_by']))->toContain('branch');
});
