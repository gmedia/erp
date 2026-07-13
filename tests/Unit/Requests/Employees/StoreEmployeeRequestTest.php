<?php

use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('employees');

describe('StoreEmployeeRequest', function () {

    test('authorize returns true', function () {
        $request = new StoreEmployeeRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new StoreEmployeeRequest;

        $rules = $request->rules();

        expect($rules)->toHaveKeys([
            'employee_id',
            'name',
            'email',
            'phone',
            'current_employment.department_id',
            'current_employment.position_id',
            'current_employment.branch_id',
            'current_employment.salary',
            'current_employment.hire_date',
            'current_employment.employment_status',
            'current_employment.termination_date',
        ]);
    });

    test('rules validation passes with valid data', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();

        $data = [
            'employee_id' => 'EMP-123',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'current_employment' => [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => Branch::factory()->create()->id,
                'company_id' => Company::factory()->create()->id,
                'salary' => '75000.00',
                'hire_date' => '2023-01-15',
                'employment_status' => 'regular',
            ],
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect(! $validator->fails())->toBeTrue();
    });

    test('rules validation fails with missing required fields', function () {
        $data = [];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('employee_id'))->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue()
            ->and($validator->errors()->has('current_employment.department_id'))->toBeTrue()
            ->and($validator->errors()->has('current_employment.position_id'))->toBeTrue()
            ->and($validator->errors()->has('current_employment.branch_id'))->toBeTrue()
            ->and($validator->errors()->has('current_employment.hire_date'))->toBeTrue()
            ->and($validator->errors()->has('current_employment.employment_status'))->toBeTrue();
    });

    test('rules validation fails with invalid email', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();

        $data = [
            'employee_id' => 'EMP-123',
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'current_employment' => [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => Branch::factory()->create()->id,
                'company_id' => Company::factory()->create()->id,
                'salary' => '75000.00',
                'hire_date' => '2023-01-15',
                'employment_status' => 'regular',
            ],
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('rules validation fails with duplicate email', function () {
        Employee::factory()->create(['email' => 'existing@example.com']);
        $department = Department::factory()->create();
        $position = Position::factory()->create();

        $data = [
            'employee_id' => 'EMP-123',
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'current_employment' => [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => Branch::factory()->create()->id,
                'company_id' => Company::factory()->create()->id,
                'salary' => '75000.00',
                'hire_date' => '2023-01-15',
                'employment_status' => 'regular',
            ],
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('rules validation fails with invalid department', function () {
        $position = Position::factory()->create();

        $data = [
            'employee_id' => 'EMP-123',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'current_employment' => [
                'department_id' => 999999,
                'position_id' => $position->id,
                'branch_id' => Branch::factory()->create()->id,
                'company_id' => Company::factory()->create()->id,
                'salary' => '75000.00',
                'hire_date' => '2023-01-15',
                'employment_status' => 'regular',
            ],
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('current_employment.department_id'))->toBeTrue();
    });

    test('rules validation fails with negative salary', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();

        $data = [
            'employee_id' => 'EMP-123',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'current_employment' => [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => Branch::factory()->create()->id,
                'company_id' => Company::factory()->create()->id,
                'salary' => '-1000',
                'hire_date' => '2023-01-15',
                'employment_status' => 'regular',
            ],
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('current_employment.salary'))->toBeTrue();
    });

    test('rules validation fails with invalid hire_date', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();

        $data = [
            'employee_id' => 'EMP-123',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'current_employment' => [
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => Branch::factory()->create()->id,
                'company_id' => Company::factory()->create()->id,
                'salary' => '75000.00',
                'hire_date' => 'invalid-date',
                'employment_status' => 'regular',
            ],
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('current_employment.hire_date'))->toBeTrue();
    });

    test('rules validation passes with valid branch id', function () {
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $branch = Branch::factory()->create();

        $data = [
            'employee_id' => 'EMP-123',
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'current_employment' => [
                'company_id' => '1',
                'department_id' => $department->id,
                'position_id' => $position->id,
                'branch_id' => $branch->id,
                'salary' => '75000.00',
                'hire_date' => '2023-01-15',
                'employment_status' => 'regular',
            ],
        ];

        $validator = validator($data, (new StoreEmployeeRequest)->rules());

        expect(! $validator->fails())->toBeTrue();
    });
});
