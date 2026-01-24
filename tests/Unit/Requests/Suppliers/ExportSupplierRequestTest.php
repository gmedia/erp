<?php

use App\Http\Requests\Suppliers\ExportSupplierRequest;


uses()->group('suppliers');

test('authorize returns true', function () {
    $request = new ExportSupplierRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportSupplierRequest;

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'branch' => ['nullable', 'exists:branches,id'],
        'category' => ['nullable', 'string'],
        'status' => ['nullable', 'string', 'in:active,inactive'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,phone,branch_id,category,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
    ]);
});
