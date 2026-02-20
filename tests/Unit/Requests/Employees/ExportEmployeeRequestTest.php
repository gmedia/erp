<?php

use App\Http\Requests\Employees\ExportEmployeeRequest;


uses()->group('employees');

test('authorize returns true', function () {
    $request = new ExportEmployeeRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules returns validation rules', function () {
    $request = new ExportEmployeeRequest;

    $rules = $request->rules();

    expect($rules)->toHaveKey('department_id')
        ->and($rules)->toHaveKey('position_id')
        ->and($rules)->toHaveKey('branch_id')
        ->and($rules)->toHaveKey('sort_by')
        ->and($rules)->toHaveKey('sort_direction');

    // Check specific validation rules
    expect($rules['search'])->toBe(['nullable', 'string']);
    expect($rules['department_id'])->toBe(['nullable', 'integer', 'exists:departments,id']);
    expect($rules['position_id'])->toBe(['nullable', 'integer', 'exists:positions,id']);
    expect($rules['branch_id'])->toBe(['nullable', 'integer', 'exists:branches,id']);
    expect($rules['employment_status'])->toBe(['nullable', 'string', 'in:regular,intern']);
    expect($rules['sort_by'])->toBe(['nullable', 'string', 'in:id,employee_id,name,email,department_id,position_id,salary,employment_status,hire_date,created_at,updated_at']);
    expect($rules['sort_direction'])->toBe(['nullable', 'string', 'in:asc,desc']);
});
