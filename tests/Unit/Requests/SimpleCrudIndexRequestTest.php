<?php

use App\Http\Requests\SimpleCrudIndexRequest;

uses()->group('requests');

test('simple crud index request returns the shared listing rules', function () {
    $request = new SimpleCrudIndexRequest;

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toEqual([
            'search' => ['nullable', 'string'],
            'sort_by' => ['nullable', 'string', 'in:id,name,created_at,updated_at'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
});
