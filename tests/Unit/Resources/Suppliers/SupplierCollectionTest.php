<?php

use App\Http\Resources\Suppliers\SupplierCollection;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('suppliers');

test('to array returns correct structure', function () {
    Supplier::factory()->count(3)->create();
    $suppliers = Supplier::with(['branch', 'category'])->paginate(2);
    
    $resource = new SupplierCollection($suppliers);
    $request = Request::create('/api/suppliers');
    
    $result = $resource->toArray($request);
    
    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['data', 'meta'])
        ->and($result['data'])->toHaveCount(2);

    $firstItem = $result['data'][0]->resolve($request);
    
    expect($firstItem)->toHaveKeys([
        'id',
        'name',
            'email',
            'phone',
            'address',
            'branch',
            'branch_id',
            'category',
            'category_id',
            'status',
            'created_at',
            'updated_at',
        ]);
});
