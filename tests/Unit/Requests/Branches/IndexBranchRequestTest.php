<?php

use App\Http\Requests\Branches\IndexBranchRequest;


uses()->group('branches');

describe('IndexBranchRequest', function () {

    test('authorize returns true', function () {
        $request = new IndexBranchRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new IndexBranchRequest;

        $rules = $request->rules();

        expect($rules)->toHaveKeys(['search', 'sort_by', 'sort_direction', 'per_page', 'page']);
    });

    test('rules validation passes with valid data', function () {
        $data = [
            'search' => 'jakarta',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'per_page' => 10,
            'page' => 1,
        ];

        $validator = validator($data, (new IndexBranchRequest)->rules());

        expect(!$validator->fails())->toBeTrue();
    });

    test('rules validation passes with empty data', function () {
        $data = [];

        $validator = validator($data, (new IndexBranchRequest)->rules());

        expect(!$validator->fails())->toBeTrue();
    });

    test('rules validation fails with invalid sort_by', function () {
        $data = ['sort_by' => 'invalid_field'];

        $validator = validator($data, (new IndexBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('sort_by'))->toBeTrue();
    });

    test('rules validation fails with invalid sort_direction', function () {
        $data = ['sort_direction' => 'invalid'];

        $validator = validator($data, (new IndexBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('sort_direction'))->toBeTrue();
    });

    test('rules validation fails with per_page too small', function () {
        $data = ['per_page' => 0];

        $validator = validator($data, (new IndexBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('per_page'))->toBeTrue();
    });

    test('rules validation fails with per_page too large', function () {
        $data = ['per_page' => 101];

        $validator = validator($data, (new IndexBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('per_page'))->toBeTrue();
    });

    test('rules validation fails with page less than 1', function () {
        $data = ['page' => 0];

        $validator = validator($data, (new IndexBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('page'))->toBeTrue();
    });

    test('rules validation passes with valid sort_by values', function () {
        $validSortByValues = ['id', 'name', 'created_at', 'updated_at'];

        foreach ($validSortByValues as $value) {
            $data = ['sort_by' => $value];

            $validator = validator($data, (new IndexBranchRequest)->rules());

            expect(!$validator->fails())->toBeTrue();
        }
    });

    test('rules validation passes with valid sort_direction values', function () {
        $validSortDirectionValues = ['asc', 'desc'];

        foreach ($validSortDirectionValues as $value) {
            $data = ['sort_direction' => $value];

            $validator = validator($data, (new IndexBranchRequest)->rules());

            expect(!$validator->fails())->toBeTrue();
        }
    });
});
