<?php

use App\Http\Resources\Warehouses\WarehouseCollection;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('warehouses');

test('to array transforms collection of warehouses', function () {
    $warehouses = Warehouse::factory()->count(3)->create();

    $collection = new WarehouseCollection($warehouses);
    $request = Request::create('/');

    $result = $collection->toArray($request);

    expect($result)->toHaveCount(3);

    expect($result[0])->toHaveKeys(['id', 'branch_id', 'branch', 'code', 'name', 'created_at', 'updated_at']);
    expect($result[0]['name'])->toBe($warehouses[0]->name);
});
