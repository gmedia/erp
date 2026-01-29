<?php

use App\Http\Resources\Products\ProductResource;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product resource transforms data correctly', function () {
    $product = Product::factory()->create();

    $resource = new ProductResource($product);
    $data = $resource->toArray(request());

    expect($data)->toHaveKeys([
        'id', 'code', 'name', 'type', 'category', 'unit', 'branch', 'cost', 'selling_price', 'status', 'created_at'
    ]);
    
    expect($data['category'])->toHaveKeys(['id', 'name']);
    expect($data['unit'])->toHaveKeys(['id', 'name', 'symbol']);
});
