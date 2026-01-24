<?php

use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branches');

describe('UpdateBranchRequest', function () {

    test('authorize returns true', function () {
        $request = new UpdateBranchRequest;

        expect($request->authorize())->toBeTrue();
    });

    test('rules returns validation rules', function () {
        $branch = Branch::factory()->create();
        $request = new UpdateBranchRequest;
        $request->setRouteResolver(function () use ($branch) {
            return new class($branch) {
                private $branch;
                public function __construct($branch) { $this->branch = $branch; }
                public function parameter($name) { return $this->branch; }
            };
        });

        $rules = $request->rules();

        expect($rules)->toHaveKey('name')
            ->and($rules['name'])->toContain('sometimes')
            ->and($rules['name'])->toContain('string')
            ->and($rules['name'])->toContain('max:255');
    });

    test('rules validation passes with valid data', function () {
        $branch = Branch::factory()->create();
        $data = ['name' => 'Updated Branch Name'];

        $request = new UpdateBranchRequest;
        $request->setRouteResolver(function () use ($branch) {
            return new class($branch) {
                private $branch;
                public function __construct($branch) { $this->branch = $branch; }
                public function parameter($name) { return $this->branch; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation passes without name field', function () {
        $branch = Branch::factory()->create();
        $data = [];

        $request = new UpdateBranchRequest;
        $request->setRouteResolver(function () use ($branch) {
            return new class($branch) {
                private $branch;
                public function __construct($branch) { $this->branch = $branch; }
                public function parameter($name) { return $this->branch; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with empty name', function () {
        $branch = Branch::factory()->create();
        $data = ['name' => ''];

        $request = new UpdateBranchRequest;
        $request->setRouteResolver(function () use ($branch) {
            return new class($branch) {
                private $branch;
                public function __construct($branch) { $this->branch = $branch; }
                public function parameter($name) { return $this->branch; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });

    test('rules validation allows same name for current branch', function () {
        $branch = Branch::factory()->create(['name' => 'Jakarta Branch']);

        $data = ['name' => 'Jakarta Branch']; // Same name as current

        $request = new UpdateBranchRequest;
        $request->setRouteResolver(function () use ($branch) {
            return new class($branch) {
                private $branch;
                public function __construct($branch) { $this->branch = $branch; }
                public function parameter($name) { return $this->branch; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('rules validation fails with duplicate name from another branch', function () {
        $existingBranch = Branch::factory()->create(['name' => 'Surabaya Branch']);
        $branch = Branch::factory()->create(['name' => 'Jakarta Branch']);

        $data = ['name' => 'Surabaya Branch']; // Name from another branch

        $request = new UpdateBranchRequest;
        $request->setRouteResolver(function () use ($branch) {
            return new class($branch) {
                private $branch;
                public function __construct($branch) { $this->branch = $branch; }
                public function parameter($name) { return $this->branch; }
            };
        });

        $validator = validator($data, $request->rules());

        expect($validator->fails())->toBeTrue()
            ->and($validator->errors()->has('name'))->toBeTrue();
    });
});
