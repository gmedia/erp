<?php

use App\Http\Requests\Suppliers\IndexSupplierRequest;
use Illuminate\Support\Facades\Validator;

test('rules are correct', function () {
    $request = new IndexSupplierRequest();

    expect($request->rules())->toBe([
        'search' => ['nullable', 'string', 'max:255'],
        'page' => ['nullable', 'integer', 'min:1'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,phone,address,branch_id,category,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'branch' => ['nullable', 'integer', 'exists:branches,id'],
        'category' => ['nullable', 'string', 'max:50'],
        'status' => ['nullable', 'string', 'in:active,inactive'],
    ]);
});


uses()->group('suppliers');

test('authorize returns true', function () {
    $request = new IndexSupplierRequest();
    expect($request->authorize())->toBeTrue();
});
