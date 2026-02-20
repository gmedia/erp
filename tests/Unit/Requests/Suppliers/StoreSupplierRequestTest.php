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
        'name' => 'required|string|max:255',
        'email' => 'nullable|email|unique:suppliers,email',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'branch_id' => 'required|exists:branches,id',
        'category_id' => 'required|exists:supplier_categories,id',
        'status' => 'required|in:active,inactive',
    ]);
});
