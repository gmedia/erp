<?php

use App\Http\Requests\Departments\StoreDepartmentRequest;

uses()->group('departments');

test('authorize returns true', function () {
    $request = new StoreDepartmentRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreDepartmentRequest();
    
    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
    ]);
});
