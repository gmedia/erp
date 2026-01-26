<?php

use App\Http\Resources\Customers\CustomerCollection;
use App\Http\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('customers');

test('to array returns correct structure', function () {
    $customers = Customer::factory()->count(3)->create();
    
    $resource = new CustomerCollection($customers);
    $request = Request::create('/api/customers');
    
    $result = $resource->toArray($request);
    
    expect($result)->toBeArray()
        ->and($result)->toHaveCount(3)
        ->and($result[0])->toHaveKeys([
            'id',
            'name',
            'email',
            'phone',
            'address',
            'branch',
            'category',
            'status',
            'notes',
            'created_at',
            'updated_at',
        ]);
});
