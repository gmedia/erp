<?php

use App\Http\Requests\Products\StoreProductRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

test('store product request validation rules', function () {
    $request = new StoreProductRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'code', 'name', 'type', 'category_id', 'unit_id', 'cost', 'selling_price', 'status'
    ]);
});

test('store product request validation fails for missing required fields', function () {
    $data = [];
    $request = new StoreProductRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->messages())->toHaveKeys(['code', 'name', 'type', 'category_id', 'unit_id', 'cost', 'selling_price', 'status']);
});

test('store product request validation passes for valid data', function () {
    $category = \App\Models\ProductCategory::factory()->create();
    $unit = \App\Models\Unit::factory()->create();

    $data = [
        'code' => 'P-001',
        'name' => 'New Product',
        'type' => 'finished_good',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'cost' => 1000,
        'selling_price' => 1500,
        'status' => 'active',
        'billing_model' => 'one_time',
        'is_recurring' => false,
        'allow_one_time_purchase' => true,
        'is_manufactured' => false,
        'is_purchasable' => true,
        'is_sellable' => true,
        'is_taxable' => true,
    ];

    $request = new StoreProductRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeFalse();
});
