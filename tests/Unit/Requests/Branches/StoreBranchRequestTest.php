<?php

use App\Http\Requests\Branches\StoreBranchRequest;

uses()->group('branches', 'requests');

test('authorize returns true', function () {
    $request = new StoreBranchRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreBranchRequest();
    
    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255', 'unique:branches,name'],
    ]);
});
