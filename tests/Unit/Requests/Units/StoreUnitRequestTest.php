<?php

use App\Http\Requests\Units\StoreUnitRequest;

uses()->group('units');

test('authorize returns true', function () {
    $request = new StoreUnitRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreUnitRequest();
    
    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:units,name'],
        'symbol' => 'nullable|string|max:10',
    ]);
});
