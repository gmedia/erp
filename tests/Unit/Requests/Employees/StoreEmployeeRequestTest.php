<?php

use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('StoreEmployeeRequest', function () {

    test('authorize returns true', function () {
        $request = new StoreEmployeeRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new StoreEmployeeRequest;

        $rules = $request->rules();

        expect($rules)->toHaveKeys([
            'name',
            'email',
            'phone',
            'department',
            'position',
            'salary',
            'hire_date'
        ]);
    });

    test('rules validation passes with valid data', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'department' => 'engineering',
            'position' => 'Software Engineer',
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with missing required fields', function () {
        $data = [];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue()
            ->and($validator->errors()->has('department'))->toBeTrue()
            ->and($validator->errors()->has('position'))->toBeTrue()
            ->and($validator->errors()->has('salary'))->toBeTrue()
            ->and($validator->errors()->has('hire_date'))->toBeTrue();
    });

    test('rules validation fails with invalid email', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'department' => 'engineering',
            'position' => 'Software Engineer',
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('rules validation fails with duplicate email', function () {
        Employee::factory()->create(['email' => 'existing@example.com']);

        $data = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'department' => 'engineering',
            'position' => 'Software Engineer',
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('rules validation fails with invalid department', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'department' => 'invalid_dept',
            'position' => 'Software Engineer',
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('department'))->toBeTrue();
    });

    test('rules validation fails with negative salary', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'department' => 'engineering',
            'position' => 'Software Engineer',
            'salary' => '-1000',
            'hire_date' => '2023-01-15',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('salary'))->toBeTrue();
    });

    test('rules validation fails with invalid hire_date', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'department' => 'engineering',
            'position' => 'Software Engineer',
            'salary' => '75000.00',
            'hire_date' => 'invalid-date',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('hire_date'))->toBeTrue();
    });

    test('rules validation passes with phone field', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'department' => 'engineering',
            'position' => 'Software Engineer',
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes without phone field', function () {
        $data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'department' => 'engineering',
            'position' => 'Software Engineer',
            'salary' => '75000.00',
            'hire_date' => '2023-01-15',
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes with valid departments', function () {
        $validDepartments = [
            'hr', 'engineering', 'sales', 'marketing', 'finance',
            'operations', 'customer_support', 'product', 'design', 'legal'
        ];

        foreach ($validDepartments as $department) {
            $data = [
                'name' => 'John Doe',
                'email' => "john.{$department}@example.com",
                'department' => $department,
                'position' => 'Software Engineer',
                'salary' => '75000.00',
                'hire_date' => '2023-01-15',
            ];

            $validator = validator($data, (new StoreEmployeeRequest)->rules());

            expect($validator->passes())->toBeTrue();
        }
    });
});
