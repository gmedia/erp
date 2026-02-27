<?php

use App\Http\Requests\Warehouses\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('warehouses');

test('authorize returns true', function () {
    $request = new UpdateWarehouseRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $warehouse = Warehouse::factory()->create();
    $request = new UpdateWarehouseRequest();
    $request->setRouteResolver(function () use ($warehouse) {
        return new class($warehouse) {
            public function __construct(private Warehouse $warehouse) {}

            public function parameter($name)
            {
                if ($name === 'warehouse') {
                    return $this->warehouse;
                }

                return null;
            }
        };
    });

    $rules = $request->rules();
    expect($rules)->toHaveKeys(['branch_id', 'code', 'name']);
});

test('rules validation passes with valid partial data', function () {
    $warehouse = Warehouse::factory()->create();
    $data = ['name' => 'Updated Warehouse'];

    $request = new UpdateWarehouseRequest();
    $request->setRouteResolver(function () use ($warehouse) {
        return new class($warehouse) {
            public function __construct(private Warehouse $warehouse) {}

            public function parameter($name)
            {
                if ($name === 'warehouse') {
                    return $this->warehouse;
                }

                return null;
            }
        };
    });

    $validator = validator($data, $request->rules());
    expect(!$validator->fails())->toBeTrue();
});
