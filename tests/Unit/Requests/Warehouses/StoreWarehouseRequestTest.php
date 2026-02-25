<?php

use App\Http\Requests\Warehouses\StoreWarehouseRequest;

uses()->group('warehouses');

test('authorize returns true', function () {
    $request = new StoreWarehouseRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreWarehouseRequest();

    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:warehouses,name'],
    ]);
});
