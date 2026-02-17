<?php

use App\Http\Requests\Units\IndexUnitRequest;

uses()->group('units');

test('authorize returns true', function () {
    $request = new IndexUnitRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexUnitRequest();

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'sort_by' => ['nullable', 'string', 'in:id,name,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
