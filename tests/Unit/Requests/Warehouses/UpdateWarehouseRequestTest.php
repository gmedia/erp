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

    $request = Mockery::mock(UpdateWarehouseRequest::class)->makePartial();

    $request->shouldReceive('route')
        ->with('warehouse')
        ->andReturn($warehouse);

    $request->shouldReceive('route')
        ->with('id')
        ->andReturn(null);

    expect($request->rules())->toEqual([
        'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:warehouses,name,' . $warehouse->id],
    ]);
});
