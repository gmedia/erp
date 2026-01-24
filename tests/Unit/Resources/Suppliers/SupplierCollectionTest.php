<?php

use App\Http\Resources\Suppliers\SupplierCollection;
use App\Models\Supplier;
use Illuminate\Http\Request;

test('to array returns correct structure', function () {
    $suppliers = Supplier::factory()->count(3)->create();
    
    $resource = new SupplierCollection($suppliers);
    $request = Request::create('/api/suppliers');
    
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
            'created_at',
            'updated_at',
        ]);
});
