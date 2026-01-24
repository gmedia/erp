<?php

use App\Http\Resources\Suppliers\SupplierResource;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('to array returns correct structure', function () {
    $branch = Branch::factory()->create(['name' => 'Test Branch']);
    $supplier = Supplier::factory()->create([
        'name' => 'Test Supplier',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
        'branch_id' => $branch->id,
        'category' => 'electronics',
        'status' => 'active',
    ]);
    
    $resource = new SupplierResource($supplier);
    $request = Request::create('/api/suppliers');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $supplier->id,
        'name' => 'Test Supplier',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
        'branch' => [
            'id' => $branch->id,
            'name' => 'Test Branch',
        ],
        'category' => 'electronics',
        'status' => 'active',
    ]);
});
