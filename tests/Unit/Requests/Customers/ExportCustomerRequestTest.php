<?php

use App\Http\Requests\Customers\ExportCustomerRequest;


uses()->group('customers');

test('authorize returns true', function () {
    $request = new ExportCustomerRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportCustomerRequest;
    
    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'branch' => ['nullable', 'exists:branches,id'],
        'category' => ['nullable', 'exists:customer_categories,id'],
        'status' => ['nullable', 'string', 'in:active,inactive'],
        'sort_by' => ['nullable', 'string', 'in:id,name,email,phone,branch_id,category_id,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'in:asc,desc'],
    ]);
});
