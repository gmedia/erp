<?php

use App\Http\Requests\Units\ExportUnitRequest;

uses()->group('units', 'requests');

test('authorize returns true', function () {
    $request = new ExportUnitRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportUnitRequest();

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'sort_by' => ['nullable', 'string', 'in:id,name,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
    ]);
});
