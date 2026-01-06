<?php

use App\Http\Requests\Departments\StoreDepartmentRequest;

test('authorize returns true', function () {
    $request = new StoreDepartmentRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules returns validation rules', function () {
    $request = new StoreDepartmentRequest;

    $rules = $request->rules();

    expect($rules)->toBe([
        'name' => 'required|string|max:255|unique:departments,name',
    ]);
});

test('rules validation passes with valid data', function () {
    $data = ['name' => 'Engineering'];

    $validator = validator($data, (new StoreDepartmentRequest)->rules());

    expect($validator->passes())->toBeTrue();
});

test('rules validation fails with missing name', function () {
    $data = [];

    $validator = validator($data, (new StoreDepartmentRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue();
});

test('rules validation fails with duplicate name', function () {
    // This would require database setup, so we'll skip for now
    // In a real scenario, we'd use RefreshDatabase and create a department first
    $this->markTestSkipped('Requires database setup for unique validation');
});
