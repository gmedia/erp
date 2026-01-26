<?php

use App\Http\Resources\Suppliers\SupplierResource;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('suppliers');

test('to array returns correct structure', function () {
    $branch = Branch::factory()->create(['name' => 'Test Branch']);
    $category = \App\Models\SupplierCategory::factory()->create(['name' => 'IT Services']);
    $supplier = Supplier::factory()->create([
        'name' => 'Test Supplier',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'status' => 'active',
    ]);
    
    $resource = new SupplierResource($supplier->load(['branch', 'category']));
    $request = Request::create('/api/suppliers');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $supplier->id,
        'name' => 'Test Supplier',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'address' => '123 Test St',
        'branch_id' => $branch->id,
        'category_id' => $category->id,
        'branch' => [
            'id' => $branch->id,
            'name' => 'Test Branch',
        ],
        'category' => [
            'id' => $category->id,
            'name' => 'IT Services',
        ],
        'status' => 'active',
    ]);
});
