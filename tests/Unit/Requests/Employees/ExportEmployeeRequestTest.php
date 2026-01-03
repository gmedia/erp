<?php

use App\Http\Requests\Employees\ExportEmployeeRequest;

test('authorize returns true', function () {
    $request = new ExportEmployeeRequest();

    expect($request->authorize())->toBeTrue();
});

test('rules returns validation rules', function () {
    $request = new ExportEmployeeRequest();

    $rules = $request->rules();

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('search')
        ->and($rules)->toHaveKey('department')
        ->and($rules)->toHaveKey('position')
        ->and($rules)->toHaveKey('sort_by')
        ->and($rules)->toHaveKey('sort_direction');

    // Check specific validation rules
    expect($rules['search'])->toBe(['nullable', 'string']);
    expect($rules['department'])->toBe(['nullable', 'string']);
    expect($rules['position'])->toBe(['nullable', 'string']);
    expect($rules['sort_by'])->toBe(['nullable', 'string', 'in:id,name,email,department,position,salary,hire_date,created_at,updated_at']);
    expect($rules['sort_direction'])->toBe(['nullable', 'in:asc,desc']);
});
