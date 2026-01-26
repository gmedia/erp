<?php

use App\Http\Requests\Suppliers\StoreSupplierRequest;


uses()->group('suppliers');

test('authorize returns true', function () {
    $request = new StoreSupplierRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreSupplierRequest;

    expect($request->rules())->toEqual([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:suppliers'],
        'phone' => ['nullable', 'string', 'max:20'],
        'address' => ['required', 'string'],
        'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        'category_id' => ['required', 'integer', 'exists:supplier_categories,id'],
        'status' => ['required', 'string', 'in:active,inactive'],
    ]);
});
