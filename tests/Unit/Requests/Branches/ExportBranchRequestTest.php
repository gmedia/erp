<?php

use App\Http\Requests\Branches\ExportBranchRequest;

describe('ExportBranchRequest', function () {

    
uses()->group('branches');

test('authorize returns true', function () {
        $request = new ExportBranchRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new ExportBranchRequest;

        $rules = $request->rules();

        expect($rules)->toHaveKeys(['search', 'sort_by', 'sort_direction']);
    });

    test('rules validation passes with valid data', function () {
        $data = [
            'search' => 'jakarta',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
        ];

        $validator = validator($data, (new ExportBranchRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes with empty data', function () {
        $data = [];

        $validator = validator($data, (new ExportBranchRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with invalid sort_by', function () {
        $data = ['sort_by' => 'invalid_field'];

        $validator = validator($data, (new ExportBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('sort_by'))->toBeTrue();
    });

    test('rules validation fails with invalid sort_direction', function () {
        $data = ['sort_direction' => 'invalid'];

        $validator = validator($data, (new ExportBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('sort_direction'))->toBeTrue();
    });

    test('rules validation passes with valid sort_by values', function () {
        $validSortByValues = ['id', 'name', 'created_at', 'updated_at'];

        foreach ($validSortByValues as $value) {
            $data = ['sort_by' => $value];

            $validator = validator($data, (new ExportBranchRequest)->rules());

            expect($validator->passes())->toBeTrue();
        }
    });

    test('rules validation passes with valid sort_direction values', function () {
        $validSortDirectionValues = ['asc', 'desc'];

        foreach ($validSortDirectionValues as $value) {
            $data = ['sort_direction' => $value];

            $validator = validator($data, (new ExportBranchRequest)->rules());

            expect($validator->passes())->toBeTrue();
        }
    });
});
