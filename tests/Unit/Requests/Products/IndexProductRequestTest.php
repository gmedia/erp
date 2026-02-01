<?php

use App\Http\Requests\Products\IndexProductRequest;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

describe('IndexProductRequest', function () {

    test('authorize returns true', function () {
        $request = new IndexProductRequest;
        expect($request->authorize())->toBeTrue();
    });

    test('rules allow optional filters', function () {
        $category = ProductCategory::factory()->create();
        $unit = Unit::factory()->create();
        $branch = Branch::factory()->create();

        $data = [
            'search' => 'test',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'branch_id' => $branch->id,
            'type' => 'finished_good',
            'status' => 'active',
            'per_page' => 10,
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ];

        $validator = validator($data, (new IndexProductRequest)->rules());
        expect($validator->passes())->toBeTrue();
    });

    test('rules fail with invalid enum values', function () {
        $data = [
            'type' => 'invalid-type',
            'status' => 'invalid-status',
            'sort_direction' => 'invalid-dir',
        ];

        $validator = validator($data, (new IndexProductRequest)->rules());
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('type'))->toBeTrue()
            ->and($validator->errors()->has('status'))->toBeTrue()
            ->and($validator->errors()->has('sort_direction'))->toBeTrue();
    });
});
