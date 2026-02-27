<?php

use App\Http\Resources\Warehouses\WarehouseResource;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('warehouses');

test('to array returns correct structure', function () {
    $branch = Branch::factory()->create(['name' => 'Branch 1']);
    $warehouse = Warehouse::factory()->create([
        'branch_id' => $branch->id,
        'code' => 'WH-001',
        'name' => 'Main Warehouse',
    ]);
    $warehouse->load(['branch']);

    $resource = new WarehouseResource($warehouse);
    $request = Request::create('/');

    $result = $resource->toArray($request);

    expect($result)->toMatchArray([
        'id' => $warehouse->id,
        'branch_id' => $branch->id,
        'branch' => [
            'id' => $branch->id,
            'name' => 'Branch 1',
        ],
        'code' => 'WH-001',
        'name' => 'Main Warehouse',
    ]);

    expect($result['created_at'])->toBeString()
        ->and($result['updated_at'])->toBeString();
});
