<?php

use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('factory creates a valid stock adjustment', function () {
    $adjustment = StockAdjustment::factory()->create();

    assertDatabaseHas('stock_adjustments', ['id' => $adjustment->id]);
});

test('relationships are defined', function () {
    $adjustment = StockAdjustment::factory()->create();

    expect($adjustment->warehouse)->not->toBeNull();
});
