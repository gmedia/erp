<?php

use App\Actions\StockAdjustments\SyncStockAdjustmentItemsAction;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('execute upserts items and removes missing ones', function () {
    $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);
    $unit = Unit::factory()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    StockAdjustmentItem::factory()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product1->id,
        'unit_id' => $unit->id,
        'quantity_before' => 10,
        'quantity_adjusted' => 1,
    ]);

    $action = new SyncStockAdjustmentItemsAction();
    $action->execute($adjustment, [
        [
            'product_id' => $product2->id,
            'unit_id' => $unit->id,
            'quantity_before' => 10,
            'quantity_adjusted' => -2,
            'unit_cost' => 100,
            'reason' => null,
        ],
    ]);

    assertDatabaseHas('stock_adjustment_items', [
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product2->id,
        'quantity_after' => 8,
        'total_cost' => 200,
    ]);

    assertDatabaseMissing('stock_adjustment_items', [
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product1->id,
    ]);
});
