<?php

use App\Http\Requests\Departments\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('departments');

describe('UpdateDepartmentRequest', function () {

    test('authorize returns true', function () {
        $request = new UpdateDepartmentRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $department = Department::factory()->create();
        $request = new UpdateDepartmentRequest;
        $request->setRouteResolver(function () use ($department) {
            return new class($department) {
                private $department;
                public function __construct($department) { $this->department = $department; }
                public function parameter($name) { return $this->department; }
            };
        });

        $rules = $request->rules();

        expect($rules)->toHaveKey('name')
            ->and($rules['name'])->toContain('sometimes')
            ->and($rules['name'])->toContain('string')
            ->and($rules['name'])->toContain('max:255');
    });

    test('rules validation passes with valid data', function () {
        $department = Department::factory()->create();
        $data = ['name' => 'Updated Engineering Department'];

        $request = new UpdateDepartmentRequest;
        $request->setRouteResolver(function () use ($department) {
            return new class($department) {
                private $department;
                public function __construct($department) { $this->department = $department; }
                public function parameter($name) { return $this->department; }
            };
        });

        $validator = validator($data, $request->rules());

        expect(!$validator->fails())->toBeTrue();
    });

    test('rules validation passes without name field', function () {
        $department = Department::factory()->create();
        $data = [];

        $request = new UpdateDepartmentRequest;
        $request->setRouteResolver(function () use ($department) {
            return new class($department) {
                private $department;
                public function __construct($department) { $this->department = $department; }
                public function parameter($name) { return $this->department; }
            };
        });

        $validator = validator($data, $request->rules());

        expect(!$validator->fails())->toBeTrue();
    });

    test('rules validation fails with empty name', function () {
        $department = Department::factory()->create();
        $data = ['name' => ''];

        $request = new UpdateDepartmentRequest;
        $request->setRouteResolver(function () use ($department) {
            return new class($department) {
                private $department;
                public function __construct($department) { $this->department = $department; }
                public function parameter($name) { return $this->department; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation fails with name too long', function () {
        $department = Department::factory()->create();
        $data = ['name' => str_repeat('a', 256)];

        $request = new UpdateDepartmentRequest;
        $request->setRouteResolver(function () use ($department) {
            return new class($department) {
                private $department;
                public function __construct($department) { $this->department = $department; }
                public function parameter($name) { return $this->department; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation allows same name for current department', function () {
        $department = Department::factory()->create(['name' => 'Engineering']);

        $data = ['name' => 'Engineering']; // Same name as current

        $request = new UpdateDepartmentRequest;
        $request->setRouteResolver(function () use ($department) {
            return new class($department) {
                private $department;
                public function __construct($department) { $this->department = $department; }
                public function parameter($name) { return $this->department; }
            };
        });

        $validator = validator($data, $request->rules());

        expect(!$validator->fails())->toBeTrue();
    });

    test('rules validation fails with duplicate name from another department', function () {
        $existingDepartment = Department::factory()->create(['name' => 'Marketing']);
        $department = Department::factory()->create(['name' => 'Engineering']);

        $data = ['name' => 'Marketing']; // Name from another department

        $request = new UpdateDepartmentRequest;
        $request->setRouteResolver(function () use ($department) {
            return new class($department) {
                private $department;
                public function __construct($department) { $this->department = $department; }
                public function parameter($name) { return $this->department; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });
});
