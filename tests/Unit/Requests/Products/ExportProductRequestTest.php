<?php

use App\Http\Requests\Products\ExportProductRequest;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

describe('ExportProductRequest', function () {

    test('authorize returns true', function () {
        $request = new ExportProductRequest;
        expect($request->authorize())->toBeTrue();
    });

    test('rules allow valid export parameters', function () {
        $category = ProductCategory::factory()->create();

        $data = [
            'search' => 'test',
            'category_id' => $category->id,
            'type' => 'finished_good',
            'status' => 'active',
            'sort_by' => 'name',
            'sort_direction' => 'desc',
        ];

        $validator = validator($data, (new ExportProductRequest)->rules());
        expect($validator->passes())->toBeTrue();
    });

    test('rules fail with invalid parameters', function () {
        $data = [
            'type' => 'invalid-type',
            'sort_by' => 'invalid-column',
        ];

        $validator = validator($data, (new ExportProductRequest)->rules());
        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('type'))->toBeTrue()
            ->and($validator->errors()->has('sort_by'))->toBeTrue();
    });
});
