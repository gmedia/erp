<?php

use App\Http\Resources\Warehouses\WarehouseResource;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('warehouses');

test('to array returns correct structure', function () {
    $warehouse = Warehouse::factory()->create([
        'name' => 'Main Warehouse',
    ]);

    $resource = new WarehouseResource($warehouse);
    $request = Request::create('/');

    $result = $resource->toArray($request);

    expect($result)->toMatchArray([
        'id' => $warehouse->id,
        'name' => 'Main Warehouse',
    ]);

    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
