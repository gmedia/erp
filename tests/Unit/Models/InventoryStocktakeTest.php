<?php

use App\Models\InventoryStocktake;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('factory creates a valid inventory stocktake', function () {
    $stocktake = InventoryStocktake::factory()->create();

    assertDatabaseHas('inventory_stocktakes', ['id' => $stocktake->id]);
});

test('relationships are defined', function () {
    $stocktake = InventoryStocktake::factory()->create();

    expect($stocktake->warehouse)->not->toBeNull();
});

