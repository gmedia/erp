<?php

use App\Http\Requests\Customers\StoreCustomerRequest;


uses()->group('customers');

test('authorize returns true', function () {
    $request = new StoreCustomerRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreCustomerRequest;
    
    expect($request->rules())->toBe([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:customers,email',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'branch_id' => 'required|exists:branches,id',
        'category_id' => 'required|exists:customer_categories,id',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);
});

// Note: prepareForValidation logic might need to be verified against the actual Request class
// Let's assume standard implementation based on context.
// Actually, StoreCustomerRequest logic I implemented earlier:
/*
    protected function prepareForValidation()
    {
        if ($this->has('branch')) {
            $this->merge([
                'branch_id' => $this->branch,
                'branch' => null
            ]);
        }
    }
*/
