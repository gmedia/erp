<?php

use App\Http\Resources\Customers\CustomerResource;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('customers');

test('to array returns correct structure', function () {
    $branch = Branch::factory()->create(['name' => 'Test Branch']);
    $category = \App\Models\CustomerCategory::factory()->create(['name' => 'Test Category']);
    $customer = Customer::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'status' => 'active',
        'notes' => 'Test Notes',
    ]);
    
    $resource = new CustomerResource($customer);
    $request = Request::create('/api/customers');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $customer->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
        'branch' => [
            'id' => $branch->id,
            'name' => 'Test Branch',
        ],
        'category' => [
            'id' => $category->id,
            'name' => 'Test Category',
        ],
        'status' => 'active',
        'notes' => 'Test Notes',
    ]);
});
