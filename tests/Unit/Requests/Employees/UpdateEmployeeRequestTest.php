<?php

use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UpdateEmployeeRequest', function () {

    test('authorize returns true', function () {
        $request = new UpdateEmployeeRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $employee = Employee::factory()->create();
        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

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

    test('rules validation passes with valid partial data', function () {
        $employee = Employee::factory()->create();
        $data = ['name' => 'Updated Name'];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes with empty data', function () {
        $employee = Employee::factory()->create();
        $data = [];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with invalid email format', function () {
        $employee = Employee::factory()->create();
        $data = ['email' => 'invalid-email'];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('rules validation allows same email for current employee', function () {
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        $data = ['email' => 'john@example.com']; // Same email as current

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with duplicate email from another employee', function () {
        $existingEmployee = Employee::factory()->create(['email' => 'existing@example.com']);
        $employee = Employee::factory()->create(['email' => 'john@example.com']);

        $data = ['email' => 'existing@example.com']; // Email from another employee

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('email'))->toBeTrue();
    });

    test('rules validation fails with invalid department', function () {
        $employee = Employee::factory()->create();
        $data = ['department' => 'invalid_dept'];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('department'))->toBeTrue();
    });

    test('rules validation fails with negative salary', function () {
        $employee = Employee::factory()->create();
        $data = ['salary' => '-1000'];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('salary'))->toBeTrue();
    });

    test('rules validation fails with invalid hire_date', function () {
        $employee = Employee::factory()->create();
        $data = ['hire_date' => 'invalid-date'];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('hire_date'))->toBeTrue();
    });

    test('rules validation passes with valid departments', function () {
        $employee = Employee::factory()->create();
        $validDepartments = [
            'hr', 'engineering', 'sales', 'marketing', 'finance',
            'operations', 'customer_support', 'product', 'design', 'legal'
        ];

        foreach ($validDepartments as $department) {
            $data = ['department' => $department];

            $request = new UpdateEmployeeRequest;
            $request->setRouteResolver(function () use ($employee) {
                return new class($employee) {
                    private $employee;
                    public function __construct($employee) { $this->employee = $employee; }
                    public function parameter($name) { return $this->employee; }
                };
            });

            $validator = validator($data, $request->rules());

            expect($validator->passes())->toBeTrue();
        }
    });

    test('rules validation passes with phone update', function () {
        $employee = Employee::factory()->create();
        $data = ['phone' => '555-1234'];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes with null phone', function () {
        $employee = Employee::factory()->create(['phone' => '555-1234']);
        $data = ['phone' => null];

        $request = new UpdateEmployeeRequest;
        $request->setRouteResolver(function () use ($employee) {
            return new class($employee) {
                private $employee;
                public function __construct($employee) { $this->employee = $employee; }
                public function parameter($name) { return $this->employee; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });
});
