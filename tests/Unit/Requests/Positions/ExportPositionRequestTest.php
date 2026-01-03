<?php

use App\Http\Requests\Positions\ExportPositionRequest;

test('authorize returns true', function () {
    $request = new ExportPositionRequest();

    expect($request->authorize())->toBeTrue();
});

test('rules returns validation rules', function () {
    $request = new ExportPositionRequest();

    $rules = $request->rules();

    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('search')
        ->and($rules)->toHaveKey('sort_by')
        ->and($rules)->toHaveKey('sort_direction');

    // Check specific validation rules
    expect($rules['search'])->toBe(['nullable', 'string']);
    expect($rules['sort_by'])->toBe(['nullable', 'string', 'in:id,name,created_at,updated_at']);
    expect($rules['sort_direction'])->toBe(['nullable', 'in:asc,desc']);
});
