<?php

use App\Models\InventoryStocktakeItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('factory creates a valid inventory stocktake item', function () {
    $item = InventoryStocktakeItem::factory()->create();

    assertDatabaseHas('inventory_stocktake_items', ['id' => $item->id]);
});

test('relationships are defined', function () {
    $item = InventoryStocktakeItem::factory()->create();

    expect($item->inventoryStocktake)->not->toBeNull()
        ->and($item->product)->not->toBeNull()
        ->and($item->unit)->not->toBeNull();
});
