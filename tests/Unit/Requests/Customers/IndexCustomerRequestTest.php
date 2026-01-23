<?php

use App\Http\Requests\Customers\IndexCustomerRequest;

test('authorize returns true', function () {
    $request = new IndexCustomerRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexCustomerRequest;
    
    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'branch' => ['nullable', 'string'],
        'customer_type' => ['nullable', 'string', 'in:individual,company'],
        'status' => ['nullable', 'string', 'in:active,inactive'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,phone,branch,customer_type,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
