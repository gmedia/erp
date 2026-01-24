<?php

use App\Http\Requests\Positions\StorePositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('positions');

describe('StorePositionRequest', function () {

    test('authorize returns true', function () {
        $request = new StorePositionRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $request = new StorePositionRequest;

        $rules = $request->rules();

        expect($rules)->toBe([
            'name' => 'required|string|max:255|unique:positions,name',
        ]);
    });

    test('rules validation passes with valid data', function () {
        $data = ['name' => 'Software Engineer'];

        $validator = validator($data, (new StorePositionRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with missing name', function () {
        $data = [];

        $validator = validator($data, (new StorePositionRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue()
            ->and($validator->errors()->first('name'))->toContain('required');
    });

    test('rules validation fails with empty name', function () {
        $data = ['name' => ''];

        $validator = validator($data, (new StorePositionRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation fails with name too long', function () {
        $data = ['name' => str_repeat('a', 256)];

        $validator = validator($data, (new StorePositionRequest)->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation includes unique constraint for name field', function () {
        $rules = (new StorePositionRequest)->rules();

        // Check that the unique rule is present in the validation rules
        expect($rules['name'])->toContain('unique:positions,name');
    });

    test('rules validation passes with unique name', function () {
        Position::factory()->create(['name' => 'Existing Position']);

        $data = ['name' => 'New Position'];

        $validator = validator($data, (new StorePositionRequest)->rules());

        expect($validator->passes())->toBeTrue();
    });
});
