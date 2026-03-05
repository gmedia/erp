<?php

use App\Actions\InventoryStocktakes\SyncInventoryStocktakeItemsAction;
use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('execute upserts items, computes result, and removes missing ones', function () {
    $user = User::factory()->create();
    actingAs($user);

    $stocktake = InventoryStocktake::factory()->create(['status' => 'draft']);
    $unit = Unit::factory()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    InventoryStocktakeItem::factory()->create([
        'inventory_stocktake_id' => $stocktake->id,
        'product_id' => $product1->id,
        'unit_id' => $unit->id,
        'system_quantity' => 10,
        'counted_quantity' => 10,
        'result' => 'match',
    ]);

    $action = new SyncInventoryStocktakeItemsAction();
    $action->execute($stocktake, [
        [
            'product_id' => $product2->id,
            'unit_id' => $unit->id,
            'system_quantity' => 5,
            'counted_quantity' => 7,
            'notes' => null,
        ],
    ]);

    assertDatabaseHas('inventory_stocktake_items', [
        'inventory_stocktake_id' => $stocktake->id,
        'product_id' => $product2->id,
        'result' => 'surplus',
    ]);

    assertDatabaseMissing('inventory_stocktake_items', [
        'inventory_stocktake_id' => $stocktake->id,
        'product_id' => $product1->id,
    ]);
});

