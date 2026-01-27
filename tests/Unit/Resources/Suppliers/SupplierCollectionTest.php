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
    
    $response = $resource->response()->getData(true);
    
    expect($response)->toBeArray()
        ->and($response)->toHaveKeys(['data', 'meta'])
        ->and($response['data'])->toHaveCount(2);

    $firstItem = $response['data'][0];
    
    expect($firstItem)->toHaveKeys([
        'id',
        'name',
        'email',
        'phone',
        'address',
        'branch',
        'category',
        'status',
        'created_at',
        'updated_at',
    ]);
});
