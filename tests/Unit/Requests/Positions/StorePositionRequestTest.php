<?php

use App\Http\Requests\Positions\StorePositionRequest;

uses()->group('positions', 'requests');

test('authorize returns true', function () {
    $request = new StorePositionRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StorePositionRequest();
    
    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:positions,name'],
    ]);
});
