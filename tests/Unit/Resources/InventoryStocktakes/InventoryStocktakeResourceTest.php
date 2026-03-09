<?php

use App\Http\Resources\InventoryStocktakes\InventoryStocktakeResource;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('to array returns correct structure', function () {
    $warehouse = Warehouse::factory()->create(['name' => 'Main WH']);
    $product = Product::factory()->create(['name' => 'Test Product']);
    $unit = Unit::factory()->create(['name' => 'PCS']);

    $stocktake = InventoryStocktake::factory()->create([
        'stocktake_number' => 'SO-TEST-0001',
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
    ]);

    InventoryStocktakeItem::factory()->create([
        'inventory_stocktake_id' => $stocktake->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'system_quantity' => 10,
        'counted_quantity' => 10,
        'result' => 'match',
    ]);

    $stocktake->load(['warehouse', 'items.product', 'items.unit']);

    $resource = new InventoryStocktakeResource($stocktake);
    $request = Request::create('/');

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys([
        'id',
        'stocktake_number',
        'warehouse',
        'stocktake_date',
        'status',
        'items',
        'created_at',
        'updated_at',
    ]);

    expect($result['stocktake_number'])->toBe('SO-TEST-0001')
        ->and($result['warehouse']['name'])->toBe('Main WH')
        ->and($result['items'])->toHaveCount(1);
});
