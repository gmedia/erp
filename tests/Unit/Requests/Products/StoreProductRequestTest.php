<?php

use App\Http\Requests\Products\StoreProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

describe('StoreProductRequest', function () {

    test('authorize returns true', function () {
        $request = new StoreProductRequest;
        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new StoreProductRequest;
        $rules = $request->rules();

        expect($rules)->toHaveKeys([
            'code',
            'name',
            'type',
            'category_id',
            'unit_id',
            'cost',
            'selling_price',
            'billing_model',
            'is_recurring',
            'allow_one_time_purchase',
            'is_manufactured',
            'is_purchasable',
            'is_sellable',
            'is_taxable',
            'status',
        ]);
    });

    test('rules validation passes with valid data', function () {
        $category = ProductCategory::factory()->create();
        $unit = Unit::factory()->create();
        $branch = Branch::factory()->create();

        $data = [
            'code' => 'P-001',
            'name' => 'Test Product',
            'type' => 'finished_good',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'branch_id' => $branch->id,
            'cost' => 100.00,
            'selling_price' => 150.00,
            'billing_model' => 'one_time',
            'is_recurring' => false,
            'allow_one_time_purchase' => true,
            'is_manufactured' => true,
            'is_purchasable' => true,
            'is_sellable' => true,
            'is_taxable' => true,
            'status' => 'active',
        ];

        $validator = validator($data, (new StoreProductRequest)->rules());
        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with missing required fields', function () {
        $data = [];
        $validator = validator($data, (new StoreProductRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->has('code'))->toBeTrue()
            ->and($validator->errors()->has('type'))->toBeTrue()
            ->and($validator->errors()->has('category_id'))->toBeTrue()
            ->and($validator->errors()->has('unit_id'))->toBeTrue()
            ->and($validator->errors()->has('cost'))->toBeTrue()
            ->and($validator->errors()->has('selling_price'))->toBeTrue()
            ->and($validator->errors()->has('status'))->toBeTrue();
    });

    test('rules validation fails with duplicate code', function () {
        Product::factory()->create(['code' => 'DUPLICATE']);
        $category = ProductCategory::factory()->create();
        $unit = Unit::factory()->create();

        $data = [
            'code' => 'DUPLICATE',
            'name' => 'Test Product',
            'type' => 'finished_good',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'cost' => 100.00,
            'selling_price' => 150.00,
            'billing_model' => 'one_time',
            'is_recurring' => false,
            'allow_one_time_purchase' => true,
            'is_manufactured' => true,
            'is_purchasable' => true,
            'is_sellable' => true,
            'is_taxable' => true,
            'status' => 'active',
        ];

        $validator = validator($data, (new StoreProductRequest)->rules());
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('code'))->toBeTrue();
    });
});
