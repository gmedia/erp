<?php

use App\Http\Requests\Departments\ExportDepartmentRequest;


uses()->group('departments');

test('authorize returns true', function () {
    $request = new ExportDepartmentRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules returns validation rules', function () {
    $request = new ExportDepartmentRequest;

    $rules = $request->rules();

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('search')
        ->and($rules)->toHaveKey('sort_by')
        ->and($rules)->toHaveKey('sort_direction');

    // Check that search is nullable string
    expect($rules['search'])->toBe(['nullable', 'string']);

    // Check that sort_by is nullable string and in allowed values
    expect($rules['sort_by'])->toBe(['nullable', 'string', 'in:id,name,created_at,updated_at']);

    // Check that sort_direction is nullable and in allowed values
    expect($rules['sort_direction'])->toBe(['nullable', 'in:asc,desc']);
});
