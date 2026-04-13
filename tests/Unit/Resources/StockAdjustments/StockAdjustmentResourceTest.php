<?php

use App\Http\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('to array returns correct structure', function () {
    $warehouse = Warehouse::factory()->create(['name' => 'Main Warehouse']);
    $product = Product::factory()->create(['name' => 'Test Product']);
    $unit = Unit::factory()->create(['name' => 'PCS']);

    $adjustment = StockAdjustment::factory()->create([
        'adjustment_number' => 'SA-TEST-0001',
        'warehouse_id' => $warehouse->id,
        'adjustment_type' => 'correction',
        'status' => 'draft',
    ]);

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'quantity_before' => 10,
        'quantity_adjusted' => -2,
        'unit_cost' => 100,
    ]);

    $adjustment->load(['warehouse', 'items.product', 'items.unit']);

    $resource = new StockAdjustmentResource($adjustment);
    $request = Request::create('/');

    $result = $resource->toArray($request);

    expect($result)->toHaveKeys([
        'id',
        'adjustment_number',
        'warehouse',
        'adjustment_date',
        'adjustment_type',
        'status',
        'items',
        'created_at',
        'updated_at',
    ]);

    expect($result['adjustment_number'])->toBe('SA-TEST-0001')
        ->and($result['warehouse']['name'])->toBe('Main Warehouse')
        ->and($result['items'])->toHaveCount(1)
        ->and($result['items'][0]['product']['name'])->toBe('Test Product')
        ->and($result['items'][0]['unit']['name'])->toBe('PCS')
        ->and($result['items'][0]['quantity_before'])->toBe('10.00')
        ->and($result['items'][0]['quantity_adjusted'])->toBe('-2.00')
        ->and($result['items'][0]['unit_cost'])->toBe('100.00');
});
