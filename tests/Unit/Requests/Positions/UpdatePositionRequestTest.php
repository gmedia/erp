<?php

use App\Http\Requests\Positions\UpdatePositionRequest;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UpdatePositionRequest', function () {

    test('authorize returns true', function () {
        $request = new UpdatePositionRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $position = Position::factory()->create();
        $request = new UpdatePositionRequest;
        $request->setRouteResolver(function () use ($position) {
            return new class($position) {
                private $position;
                public function __construct($position) { $this->position = $position; }
                public function parameter($name) { return $this->position; }
            };
        });

        $rules = $request->rules();

        expect($rules)->toHaveKey('name')
            ->and($rules['name'])->toContain('sometimes')
            ->and($rules['name'])->toContain('string')
            ->and($rules['name'])->toContain('max:255');
    });

    test('rules validation passes with valid data', function () {
        $position = Position::factory()->create();
        $data = ['name' => 'Updated Senior Developer'];

        $request = new UpdatePositionRequest;
        $request->setRouteResolver(function () use ($position) {
            return new class($position) {
                private $position;
                public function __construct($position) { $this->position = $position; }
                public function parameter($name) { return $this->position; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes without name field', function () {
        $position = Position::factory()->create();
        $data = [];

        $request = new UpdatePositionRequest;
        $request->setRouteResolver(function () use ($position) {
            return new class($position) {
                private $position;
                public function __construct($position) { $this->position = $position; }
                public function parameter($name) { return $this->position; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with empty name', function () {
        $position = Position::factory()->create();
        $data = ['name' => ''];

        $request = new UpdatePositionRequest;
        $request->setRouteResolver(function () use ($position) {
            return new class($position) {
                private $position;
                public function __construct($position) { $this->position = $position; }
                public function parameter($name) { return $this->position; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation fails with name too long', function () {
        $position = Position::factory()->create();
        $data = ['name' => str_repeat('a', 256)];

        $request = new UpdatePositionRequest;
        $request->setRouteResolver(function () use ($position) {
            return new class($position) {
                private $position;
                public function __construct($position) { $this->position = $position; }
                public function parameter($name) { return $this->position; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation allows same name for current position', function () {
        $position = Position::factory()->create(['name' => 'Developer']);

        $data = ['name' => 'Developer']; // Same name as current

        $request = new UpdatePositionRequest;
        $request->setRouteResolver(function () use ($position) {
            return new class($position) {
                private $position;
                public function __construct($position) { $this->position = $position; }
                public function parameter($name) { return $this->position; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with duplicate name from another position', function () {
        $existingPosition = Position::factory()->create(['name' => 'Manager']);
        $position = Position::factory()->create(['name' => 'Developer']);

        $data = ['name' => 'Manager']; // Name from another position

        $request = new UpdatePositionRequest;
        $request->setRouteResolver(function () use ($position) {
            return new class($position) {
                private $position;
                public function __construct($position) { $this->position = $position; }
                public function parameter($name) { return $this->position; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });
});
