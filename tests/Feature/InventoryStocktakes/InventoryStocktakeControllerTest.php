<?php

use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

describe('Inventory Stocktake API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'inventory_stocktake',
            'inventory_stocktake.create',
            'inventory_stocktake.edit',
            'inventory_stocktake.delete',
        ]);

        actingAs($user);
    });

    test('index returns paginated stocktakes', function () {
        InventoryStocktake::factory()->count(3)->create(['status' => 'draft']);

        $response = getJson('/api/inventory-stocktakes?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'stocktake_number',
                        'warehouse',
                        'stocktake_date',
                        'status',
                        'product_category',
                    ],
                ],
            ]);
    });

    test('store creates a stocktake with items and auto-generates number', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $payload = [
            'warehouse_id' => $warehouse->id,
            'stocktake_date' => now()->toDateString(),
            'status' => 'draft',
            'notes' => 'Initial count',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'system_quantity' => 10,
                    'counted_quantity' => 12,
                    'notes' => 'Found extra',
                ],
            ],
        ];

        $response = postJson('/api/inventory-stocktakes', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.warehouse.id', $warehouse->id)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(1, 'data.items');

        $stocktakeId = $response->json('data.id');
        $stocktakeNumber = $response->json('data.stocktake_number');

        expect($stocktakeId)->not->toBeNull();
        expect($stocktakeNumber)->toStartWith('SO-');

        assertDatabaseHas('inventory_stocktakes', [
            'id' => $stocktakeId,
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
        ]);

        assertDatabaseHas('inventory_stocktake_items', [
            'inventory_stocktake_id' => $stocktakeId,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'result' => 'surplus',
        ]);
    });

    test('show returns a stocktake with items', function () {
        $stocktake = InventoryStocktake::factory()->create();

        $response = getJson('/api/inventory-stocktakes/' . $stocktake->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $stocktake->id);
    });

    test('update updates a stocktake and syncs items', function () {
        $warehouse = Warehouse::factory()->create();
        $stocktake = InventoryStocktake::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
        ]);

        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $payload = [
            'stocktake_number' => 'SO-UPDATED-TEST',
            'status' => 'in_progress',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'system_quantity' => 5,
                    'counted_quantity' => 5,
                    'notes' => 'Match',
                ],
            ],
        ];

        $response = putJson('/api/inventory-stocktakes/' . $stocktake->id, $payload);

        $response->assertOk()
            ->assertJsonPath('data.stocktake_number', 'SO-UPDATED-TEST')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonCount(1, 'data.items');

        assertDatabaseHas('inventory_stocktakes', [
            'id' => $stocktake->id,
            'stocktake_number' => 'SO-UPDATED-TEST',
            'status' => 'in_progress',
        ]);

        assertDatabaseHas('inventory_stocktake_items', [
            'inventory_stocktake_id' => $stocktake->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'result' => 'match',
        ]);
    });

    test('destroy cancels a stocktake (no hard delete)', function () {
        $stocktake = InventoryStocktake::factory()->create(['status' => 'draft']);

        $response = deleteJson('/api/inventory-stocktakes/' . $stocktake->id);

        $response->assertNoContent();

        assertDatabaseHas('inventory_stocktakes', [
            'id' => $stocktake->id,
            'status' => 'cancelled',
        ]);
    });

    test('getItems returns nested items with product and unit relations', function () {
        $stocktake = InventoryStocktake::factory()->create(['status' => 'draft']);
        $product = Product::factory()->create(['name' => 'Widget A']);
        $unit = Unit::factory()->create(['name' => 'pcs']);

        InventoryStocktakeItem::factory()->create([
            'inventory_stocktake_id' => $stocktake->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
            'system_quantity' => 12,
            'counted_quantity' => 10,
            'variance' => -2,
            'result' => 'deficit',
        ]);

        $response = getJson('/api/inventory-stocktakes/' . $stocktake->id . '/items');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'product' => ['id', 'name'],
                        'unit' => ['id', 'name'],
                        'system_quantity',
                        'counted_quantity',
                        'variance',
                        'result',
                        'notes',
                        'counted_by',
                        'counted_at',
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product.name', 'Widget A')
            ->assertJsonPath('data.0.unit.name', 'pcs')
            ->assertJsonPath('data.0.result', 'deficit');
    });

    test('getItems returns empty data array when stocktake has no items', function () {
        $stocktake = InventoryStocktake::factory()->create(['status' => 'draft']);

        getJson('/api/inventory-stocktakes/' . $stocktake->id . '/items')
            ->assertOk()
            ->assertJsonPath('data', []);
    });

    test('syncItems upserts items, computes variance, and removes missing ones', function () {
        $stocktake = InventoryStocktake::factory()->create(['status' => 'draft']);
        $unit = Unit::factory()->create();
        $existingProduct = Product::factory()->create();
        $newProduct = Product::factory()->create();

        InventoryStocktakeItem::factory()->create([
            'inventory_stocktake_id' => $stocktake->id,
            'product_id' => $existingProduct->id,
            'unit_id' => $unit->id,
            'system_quantity' => 10,
            'counted_quantity' => 10,
            'result' => 'match',
        ]);

        $payload = [
            'items' => [
                [
                    'product_id' => $newProduct->id,
                    'unit_id' => $unit->id,
                    'system_quantity' => 5,
                    'counted_quantity' => 8,
                    'notes' => 'Found extras',
                ],
            ],
        ];

        $response = postJson('/api/inventory-stocktakes/' . $stocktake->id . '/items', $payload);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product.id', $newProduct->id)
            ->assertJsonPath('data.0.result', 'surplus');

        assertDatabaseHas('inventory_stocktake_items', [
            'inventory_stocktake_id' => $stocktake->id,
            'product_id' => $newProduct->id,
            'result' => 'surplus',
        ]);

        assertDatabaseMissing('inventory_stocktake_items', [
            'inventory_stocktake_id' => $stocktake->id,
            'product_id' => $existingProduct->id,
        ]);
    });

    test('syncItems validates payload', function () {
        $stocktake = InventoryStocktake::factory()->create(['status' => 'draft']);

        postJson('/api/inventory-stocktakes/' . $stocktake->id . '/items', ['items' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    });
});
