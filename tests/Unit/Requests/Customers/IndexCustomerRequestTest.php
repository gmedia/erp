<?php

use App\Http\Requests\Customers\IndexCustomerRequest;


uses()->group('customers');

test('authorize returns true', function () {
    $request = new IndexCustomerRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexCustomerRequest;
    
    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'branch_id' => ['nullable', 'exists:branches,id'],
        'category_id' => ['nullable', 'exists:customer_categories,id'],
        'status' => ['nullable', 'string', 'in:active,inactive'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,phone,address,branch_id,category_id,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
