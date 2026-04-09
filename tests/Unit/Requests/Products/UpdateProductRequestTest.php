<?php

use App\Http\Requests\Products\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        expect(! $validator->fails())->toBeTrue();
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

        $request = new UpdateProductRequest;
        $request->setMethod('PUT');
        // We simulate the route parameter by mocking it if necessary, but here we just test the rules directly
        // The ignore part might need the actual product id

        $validator = validator($data, $request->rules());
        // Without the route param, it might fail because it doesn't know what to ignore
        // Expecting failure here because we haven't told the validator about the route param
        expect($validator->fails())->toBeTrue();

        // Now simulate the request with the product to be ignored
        $request = new UpdateProductRequest;
        $request->setRouteResolver(function () use ($product1) {
            return new class($product1)
            {
                public function __construct(public $product)
                {
                    // No-op constructor for promoted property in anonymous route model stub.
                }

                public function parameter($key, $default = null)
                {
                    return match ($key) {
                        'product' => $this->product,
                        default => $default,
                    };
                }
            };
        });

        $data = [
            'code' => 'P-001', // its own code
        ];

        $validator = validator($data, $request->rules());
        expect(! $validator->fails())->toBeTrue();
    });
});
