<?php

use App\Models\StockAdjustmentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('factory creates a valid stock adjustment item', function () {
    $item = StockAdjustmentItem::factory()->create();

    assertDatabaseHas('stock_adjustment_items', ['id' => $item->id]);
});

test('relationships are defined', function () {
    $item = StockAdjustmentItem::factory()->create();

    expect($item->stockAdjustment)->not->toBeNull()
        ->and($item->product)->not->toBeNull()
        ->and($item->unit)->not->toBeNull();
});
