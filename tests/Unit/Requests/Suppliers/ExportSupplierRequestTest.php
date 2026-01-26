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
        'branch_id' => ['nullable', 'exists:branches,id'],
        'category_id' => ['nullable', 'exists:supplier_categories,id'],
        'status' => ['nullable', 'string', 'in:active,inactive'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,phone,branch_id,category_id,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
    ]);
});
