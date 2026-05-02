<?php

use App\Http\Resources\StockMonitor\StockMonitorResource;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('stock-monitor');

test('stock monitor resource returns stock movement payload without unit details', function () {
    $branch = Branch::factory()->create(['name' => 'Monitoring Branch']);
    $category = ProductCategory::factory()->create(['name' => 'Finished Goods']);
    $unit = Unit::factory()->create(['name' => 'BOX']);
    $product = Product::factory()->create([
        'code' => 'PRD-STK-001',
        'name' => 'Stock Monitor Product',
        'product_category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);
    $warehouse = Warehouse::factory()->create([
        'branch_id' => $branch->id,
        'code' => 'WH-STK',
        'name' => 'Stock Monitor Warehouse',
    ]);

    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'moved_at' => '2026-04-12 11:45:00',
    ]);
    $movement->load(['product.category', 'product.unit', 'warehouse.branch']);
    $movement->setAttribute('quantity_on_hand', '8.00');
    $movement->setAttribute('average_cost', '1500.00');
    $movement->setAttribute('stock_value', '12000.00');

    $data = (new StockMonitorResource($movement))->toArray(new Request);

    expect($data)->toMatchArray([
        'id' => $movement->id,
        'product' => [
            'id' => $product->id,
            'code' => 'PRD-STK-001',
            'name' => 'Stock Monitor Product',
            'category' => [
                'id' => $category->id,
                'name' => 'Finished Goods',
            ],
        ],
        'warehouse' => [
            'id' => $warehouse->id,
            'code' => 'WH-STK',
            'name' => 'Stock Monitor Warehouse',
            'branch' => [
                'id' => $branch->id,
                'name' => 'Monitoring Branch',
            ],
        ],
        'quantity_on_hand' => '8.00',
        'average_cost' => '1500.00',
        'stock_value' => '12000.00',
        'moved_at' => $movement->moved_at?->toIso8601String(),
    ]);

    expect($data['product'])->not->toHaveKey('unit');
});
