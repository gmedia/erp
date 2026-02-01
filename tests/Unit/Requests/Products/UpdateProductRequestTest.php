<?php

use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class)->group('products');

describe('UpdateProductRequest', function () {

    test('authorize returns true', function () {
        $request = new UpdateProductRequest;
        expect($request->authorize())->toBeTrue();
    });

    test('rules validation passes with partial valid data', function () {
        $data = [
            'name' => 'Updated Name',
        ];

        $validator = validator($data, (new UpdateProductRequest)->rules());
        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with invalid status', function () {
        $data = [
            'status' => 'invalid-status',
        ];

        $validator = validator($data, (new UpdateProductRequest)->rules());
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('status'))->toBeTrue();
    });

    test('rules validation handles unique code correctly', function () {
        $product1 = Product::factory()->create(['code' => 'P-001']);
        $product2 = Product::factory()->create(['code' => 'P-002']);

        // Test updating to a code that already exists on another product
        $data = [
            'code' => 'P-002',
        ];

        $request = new UpdateProductRequest();
        $request->setMethod('PUT');
        // We simulate the route parameter by mocking it if necessary, but here we just test the rules directly
        // The ignore part might need the actual product id
        
        $validator = validator($data, $request->rules());
        // Without the route param, it might fail because it doesn't know what to ignore
        // Expecting failure here because we haven't told the validator about the route param
        expect($validator->fails())->toBeTrue();

        // Now simulate the request with the product to be ignored
        $request = Mockery::mock(UpdateProductRequest::class)->makePartial();
        $request->shouldReceive('route')->with('product')->andReturn($product1);
        
        $data = [
            'code' => 'P-001', // its own code
        ];
        
        $validator = validator($data, $request->rules());
        expect($validator->passes())->toBeTrue();
    });
});
