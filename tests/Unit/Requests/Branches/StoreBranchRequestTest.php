<?php

use App\Http\Requests\Branches\StoreBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

describe('StoreBranchRequest', function () {

    test('authorize returns true', function () {
        $request = new StoreBranchRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new StoreBranchRequest;

        $rules = $request->rules();

        expect($rules)->toBe([
            'name' => 'required|string|max:255|unique:branches,name',
        ]);
    });

    test('rules validation passes with valid data', function () {
        $data = ['name' => 'Jakarta Branch'];

        $validator = validator($data, (new StoreBranchRequest)->rules());

        expect(!$validator->fails())->toBeTrue();
    });

    test('rules validation fails with missing name', function () {
        $data = [];

        $validator = validator($data, (new StoreBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->first('name'))->toContain('required');
    });

    test('rules validation fails with empty name', function () {
        $data = ['name' => ''];

        $validator = validator($data, (new StoreBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation fails with name too long', function () {
        $data = ['name' => str_repeat('a', 256)];

        $validator = validator($data, (new StoreBranchRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation includes unique constraint for name field', function () {
        $rules = (new StoreBranchRequest)->rules();

        // Check that the unique rule is present in the validation rules
        expect($rules['name'])->toContain('unique:branches,name');
    });

    test('rules validation passes with unique name', function () {
        Branch::factory()->create(['name' => 'Existing Branch']);

        $data = ['name' => 'New Branch'];

        $validator = validator($data, (new StoreBranchRequest)->rules());

        expect(!$validator->fails())->toBeTrue();
    });
});
