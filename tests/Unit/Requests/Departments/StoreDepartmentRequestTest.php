<?php

use App\Http\Requests\Departments\StoreDepartmentRequest;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('departments');

describe('StoreDepartmentRequest', function () {

    test('authorize returns true', function () {
        $request = new StoreDepartmentRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new StoreDepartmentRequest;

        $rules = $request->rules();

        expect($rules)->toBe([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);
    });

    test('rules validation passes with valid data', function () {
        $data = ['name' => 'Engineering Department'];

        $validator = validator($data, (new StoreDepartmentRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with missing name', function () {
        $data = [];

        $validator = validator($data, (new StoreDepartmentRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->first('name'))->toContain('required');
    });

    test('rules validation fails with empty name', function () {
        $data = ['name' => ''];

        $validator = validator($data, (new StoreDepartmentRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation fails with name too long', function () {
        $data = ['name' => str_repeat('a', 256)];

        $validator = validator($data, (new StoreDepartmentRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation includes unique constraint for name field', function () {
        $rules = (new StoreDepartmentRequest)->rules();

        // Check that the unique rule is present in the validation rules
        expect($rules['name'])->toContain('unique:departments,name');
    });

    test('rules validation passes with unique name', function () {
        Department::factory()->create(['name' => 'Existing Department']);

        $data = ['name' => 'New Department'];

        $validator = validator($data, (new StoreDepartmentRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

});
