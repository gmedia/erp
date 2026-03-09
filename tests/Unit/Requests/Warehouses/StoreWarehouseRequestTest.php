<?php

use App\Http\Requests\Warehouses\StoreWarehouseRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('warehouses');

test('authorize returns true', function () {
    $request = new StoreWarehouseRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreWarehouseRequest;

    $rules = $request->rules();
    expect($rules)->toHaveKeys(['branch_id', 'code', 'name']);
});

test('rules validation passes with valid data', function () {
    $branch = Branch::factory()->create();
    $data = [
        'branch_id' => $branch->id,
        'code' => 'WH-001',
        'name' => 'Main Warehouse',
    ];

    $request = new StoreWarehouseRequest;
    $validator = validator($data, $request->rules());

    expect(! $validator->fails())->toBeTrue();
});
