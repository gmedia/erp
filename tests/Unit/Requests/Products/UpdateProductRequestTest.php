<?php

use App\Http\Requests\Products\UpdateProductRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('products');

test('update product request validation rules', function () {
    $request = new UpdateProductRequest();
    $rules = $request->rules();

    expect($rules['name'])->toContain('sometimes');
    expect($rules['code'])->toContain('sometimes');
});

test('update product request validation passes for partial data', function () {
    $product = \App\Models\Product::factory()->create();

    $data = [
        'name' => 'Updated Name',
    ];

    $request = new UpdateProductRequest();
    $request->offsetSet('product', $product); // Simulate route parameter

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeFalse();
});
