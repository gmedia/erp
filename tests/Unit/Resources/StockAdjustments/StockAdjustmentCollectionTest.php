<?php

use App\Http\Resources\StockAdjustments\StockAdjustmentCollection;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('collection uses StockAdjustmentResource', function () {
    $items = StockAdjustment::factory()->count(2)->create(['status' => 'draft']);

    $collection = new StockAdjustmentCollection($items);

    expect($collection->collects)->toBe(\App\Http\Resources\StockAdjustments\StockAdjustmentResource::class);
});
