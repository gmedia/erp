<?php

use App\Http\Requests\Employees\SyncPermissionsRequest;

uses()->group('employees');

test('authorize returns true', function () {
    $request = new SyncPermissionsRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules returns permissions array validation', function () {
    $request = new SyncPermissionsRequest;

    expect($request->rules())->toEqual([
        'permissions' => 'array',
        'permissions.*' => 'exists:permissions,id',
    ]);
});

test('messages returns custom permission validation messages', function () {
    $request = new SyncPermissionsRequest;

    expect($request->messages())->toEqual([
        'permissions.array' => 'The permissions must be an array.',
        'permissions.*.exists' => 'One or more selected permissions do not exist.',
    ]);
});
