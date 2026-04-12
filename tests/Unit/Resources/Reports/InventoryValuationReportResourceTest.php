<?php

use App\Http\Resources\Reports\InventoryValuationReportResource;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('reports');

test('inventory valuation report resource returns stock movement payload with unit details', function () {
    $branch = Branch::factory()->create(['name' => 'Central Branch']);
    $category = ProductCategory::factory()->create(['name' => 'Raw Material']);
    $unit = Unit::factory()->create(['name' => 'PCS']);
    $product = Product::factory()->create([
        'code' => 'PRD-INV-001',
        'name' => 'Inventory Product',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);
    $warehouse = Warehouse::factory()->create([
        'branch_id' => $branch->id,
        'code' => 'WH-INV',
        'name' => 'Inventory Warehouse',
    ]);

    $movement = StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'moved_at' => '2026-04-12 10:15:00',
    ]);
    $movement->load(['product.category', 'product.unit', 'warehouse.branch']);
    $movement->setAttribute('quantity_on_hand', '12.50');
    $movement->setAttribute('average_cost', '4500.25');
    $movement->setAttribute('stock_value', '56253.12');

    $data = (new InventoryValuationReportResource($movement))->toArray(new Request);

    expect($data)->toMatchArray([
        'id' => $movement->id,
        'product' => [
            'id' => $product->id,
            'code' => 'PRD-INV-001',
            'name' => 'Inventory Product',
            'category' => [
                'id' => $category->id,
                'name' => 'Raw Material',
            ],
            'unit' => [
                'id' => $unit->id,
                'name' => 'PCS',
            ],
        ],
        'warehouse' => [
            'id' => $warehouse->id,
            'code' => 'WH-INV',
            'name' => 'Inventory Warehouse',
            'branch' => [
                'id' => $branch->id,
                'name' => 'Central Branch',
            ],
        ],
        'quantity_on_hand' => '12.50',
        'average_cost' => '4500.25',
        'stock_value' => '56253.12',
        'moved_at' => $movement->moved_at?->toIso8601String(),
    ]);
});
