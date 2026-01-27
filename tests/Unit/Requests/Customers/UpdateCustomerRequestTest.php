<?php

use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;


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
        'name' => 'sometimes|required|string|max:255',
        'email' => [
            'sometimes',
            'required',
            'email',
            \Illuminate\Validation\Rule::unique('customers', 'email')->ignore($customer->id),
        ],
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'branch_id' => 'sometimes|required|exists:branches,id',
        'category_id' => 'sometimes|required|exists:customer_categories,id',
        'status' => 'sometimes|required|in:active,inactive',
        'notes' => 'nullable|string',
    ]);
});
