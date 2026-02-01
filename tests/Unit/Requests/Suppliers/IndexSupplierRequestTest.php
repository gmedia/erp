<?php

use App\Http\Requests\Suppliers\IndexSupplierRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('suppliers');

test('rules are correct', function () {
    $request = new IndexSupplierRequest();

    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'branch_id' => ['nullable', 'exists:branches,id'],
        'category_id' => ['nullable', 'exists:supplier_categories,id'],
        'status' => ['nullable', 'string', 'in:active,inactive'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,phone,address,branch_id,category_id,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});



test('authorize returns true', function () {
    $request = new IndexSupplierRequest();
    expect($request->authorize())->toBeTrue();
});
