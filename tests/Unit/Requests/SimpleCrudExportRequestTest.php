<?php

use App\Http\Requests\SimpleCrudExportRequest;

uses()->group('requests');

test('simple crud export request returns the shared listing rules', function () {
    $request = new SimpleCrudExportRequest;

    expect($request->authorize())->toBeTrue()
        ->and($request->rules())->toEqual([
            'search' => ['nullable', 'string'],
            'sort_by' => ['nullable', 'string', 'in:id,name,created_at,updated_at'],
            'sort_direction' => ['nullable', 'in:asc,desc'],
        ]);
});
