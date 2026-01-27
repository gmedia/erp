<?php

use App\Http\Requests\CustomerCategories\StoreCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('customer_categories');

describe('StoreCustomerCategoryRequest', function () {

    test('authorize returns true', function () {
        $request = new StoreCustomerCategoryRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules validation passes with valid data', function () {
        $data = ['name' => 'Engineering Category'];

        $validator = validator($data, (new StoreCustomerCategoryRequest)->rules());

        expect(!$validator->fails())->toBeTrue();
    });

    test('rules validation fails with missing name', function () {
        $data = [];

        $validator = validator($data, (new StoreCustomerCategoryRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->first('name'))->toContain('required');
    });

    test('rules validation fails with empty name', function () {
        $data = ['name' => ''];

        $validator = validator($data, (new StoreCustomerCategoryRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation fails with name too long', function () {
        $data = ['name' => str_repeat('a', 256)];

        $validator = validator($data, (new StoreCustomerCategoryRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation passes with unique name', function () {
        CustomerCategory::factory()->create(['name' => 'Existing Category']);

        $data = ['name' => 'New Category'];

        $validator = validator($data, (new StoreCustomerCategoryRequest)->rules());

        expect(!$validator->fails())->toBeTrue();
    });

});
