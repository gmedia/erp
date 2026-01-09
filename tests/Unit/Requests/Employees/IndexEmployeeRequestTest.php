<?php

use App\Http\Requests\Employees\IndexEmployeeRequest;

describe('IndexEmployeeRequest', function () {

    test('authorize returns true', function () {
        $request = new IndexEmployeeRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new IndexEmployeeRequest;

        $rules = $request->rules();

        expect($rules)->toHaveKeys([
            'search',
            'department',
            'position',
            'salary_min',
            'salary_max',
            'hire_date_from',
            'hire_date_to',
            'sort_by',
            'sort_direction',
            'per_page',
            'page'
        ]);
    });

    test('rules validation passes with valid data', function () {
        $data = [
            'search' => 'john',
            'department' => 'engineering',
            'position' => 'developer',
            'salary_min' => 50000,
            'salary_max' => 100000,
            'hire_date_from' => '2023-01-01',
            'hire_date_to' => '2023-12-31',
            'sort_by' => 'name',
            'sort_direction' => 'asc',
            'per_page' => 10,
            'page' => 1,
        ];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes with empty data', function () {
        $data = [];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with invalid sort_by', function () {
        $data = ['sort_by' => 'invalid_field'];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('sort_by'))->toBeTrue();
    });

    test('rules validation fails with invalid sort_direction', function () {
        $data = ['sort_direction' => 'invalid'];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('sort_direction'))->toBeTrue();
    });

    test('rules validation fails with negative salary_min', function () {
        $data = ['salary_min' => -1000];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('salary_min'))->toBeTrue();
    });

    test('rules validation fails with negative salary_max', function () {
        $data = ['salary_max' => -1000];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('salary_max'))->toBeTrue();
    });

    test('rules validation fails with invalid hire_date_from', function () {
        $data = ['hire_date_from' => 'invalid-date'];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('hire_date_from'))->toBeTrue();
    });

    test('rules validation fails with invalid hire_date_to', function () {
        $data = ['hire_date_to' => 'invalid-date'];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('hire_date_to'))->toBeTrue();
    });

    test('rules validation fails with per_page too small', function () {
        $data = ['per_page' => 0];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('per_page'))->toBeTrue();
    });

    test('rules validation fails with per_page too large', function () {
        $data = ['per_page' => 101];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('per_page'))->toBeTrue();
    });

    test('rules validation fails with page less than 1', function () {
        $data = ['page' => 0];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('page'))->toBeTrue();
    });

    test('rules validation passes with valid sort_by values', function () {
        $validSortByValues = ['id', 'name', 'email', 'department', 'position', 'salary', 'hire_date', 'created_at', 'updated_at'];

        foreach ($validSortByValues as $value) {
            $data = ['sort_by' => $value];

            $validator = validator($data, (new IndexEmployeeRequest)->rules());

            expect($validator->passes())->toBeTrue();
        }
    });

    test('rules validation passes with valid sort_direction values', function () {
        $validSortDirectionValues = ['asc', 'desc'];

        foreach ($validSortDirectionValues as $value) {
            $data = ['sort_direction' => $value];

            $validator = validator($data, (new IndexEmployeeRequest)->rules());

            expect($validator->passes())->toBeTrue();
        }
    });

    test('rules validation passes with valid salary_min and salary_max', function () {
        $data = [
            'salary_min' => 30000,
            'salary_max' => 150000,
        ];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes with valid hire_date range', function () {
        $data = [
            'hire_date_from' => '2023-01-01',
            'hire_date_to' => '2023-12-31',
        ];

        $validator = validator($data, (new IndexEmployeeRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });
});
