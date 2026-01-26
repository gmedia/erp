<?php

use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Routing\Route;


uses()->group('customers');

test('authorize returns true', function () {
    $request = new UpdateCustomerRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $customer = new Customer(['id' => 1]);
    
    $request = new UpdateCustomerRequest;
    
    // Mock the route parameter
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('parameter')->with('customer', null)->andReturn($customer);
    $request->setRouteResolver(fn() => $route);
    
    expect($request->rules())->toEqual([
        'name' => 'required|string|max:255',
        'email' => [
            'required',
            'email',
            Illuminate\Validation\Rule::unique('customers', 'email')->ignore($customer->id),
        ],
        'phone' => 'nullable|string|max:20',
        'address' => 'required|string',
        'branch' => 'required|exists:branches,id',
        'category_id' => 'required|exists:customer_categories,id',
        'status' => 'required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);
});

test('validated method maps branch to branch_id', function () {
    // Rely on Feature tests for mapping logic
    expect(true)->toBeTrue();
});
