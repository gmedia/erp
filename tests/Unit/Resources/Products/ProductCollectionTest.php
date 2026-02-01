<?php

use App\Http\Resources\Products\ProductCollection;
use App\Http\Resources\Products\ProductResource;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

test('ProductCollection wraps resources in data key', function () {
    Product::factory()->count(3)->create();
    $products = Product::all();

    $collection = new ProductCollection($products);
    $data = $collection->toArray(request());

    expect($data)->toHaveKey('data')
        ->and($data['data'])->toHaveCount(3)
        ->and($data['data'][0])->toBeInstanceOf(ProductResource::class);
});
