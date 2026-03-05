<?php

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-adjustments');

describe('Stock Adjustment API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'stock_adjustment',
            'stock_adjustment.create',
            'stock_adjustment.edit',
            'stock_adjustment.delete',
        ]);

        actingAs($user);
    });

    test('index returns paginated adjustments', function () {
        StockAdjustment::factory()->count(3)->create(['status' => 'draft']);

        $response = getJson('/api/stock-adjustments?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'adjustment_number',
                        'warehouse',
                        'adjustment_date',
                        'adjustment_type',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);
    });

    test('index excludes cancelled adjustments by default', function () {
        StockAdjustment::factory()->create(['status' => 'cancelled']);
        StockAdjustment::factory()->create(['status' => 'draft']);

        $response = getJson('/api/stock-adjustments');
        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.status'))->toBe('draft');
    });

    test('index can include cancelled adjustments when filtered by status', function () {
        StockAdjustment::factory()->create(['status' => 'cancelled']);

        $response = getJson('/api/stock-adjustments?status=cancelled');
        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.status'))->toBe('cancelled');
    });

    test('store creates adjustment with items', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $response = postJson('/api/stock-adjustments', [
            'adjustment_number' => 'SA-TEST-0001',
            'warehouse_id' => $warehouse->id,
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => 'correction',
            'status' => 'draft',
            'notes' => 'Test notes',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity_before' => 10,
                    'quantity_adjusted' => -2,
                    'unit_cost' => 100,
                    'reason' => null,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['adjustment_number' => 'SA-TEST-0001']);

        assertDatabaseHas('stock_adjustments', [
            'adjustment_number' => 'SA-TEST-0001',
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
        ]);

        assertDatabaseHas('stock_adjustment_items', [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
        ]);
    });

    test('store auto-generates adjustment_number when empty', function () {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $response = postJson('/api/stock-adjustments', [
            'warehouse_id' => $warehouse->id,
            'adjustment_date' => now()->toDateString(),
            'adjustment_type' => 'correction',
            'status' => 'draft',
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity_adjusted' => 1,
                ],
            ],
        ]);

        $response->assertCreated();

        $adjustmentNumber = $response->json('data.adjustment_number');
        expect($adjustmentNumber)->toBeString()->and($adjustmentNumber)->toStartWith('SA-');
    });

    test('update modifies adjustment and syncs items', function () {
        $warehouse = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $unit = Unit::factory()->create();

        $adjustment = StockAdjustment::factory()->create([
            'warehouse_id' => $warehouse->id,
            'status' => 'draft',
        ]);

        $response = putJson("/api/stock-adjustments/{$adjustment->id}", [
            'status' => 'approved',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'unit_id' => $unit->id,
                    'quantity_adjusted' => 5,
                ],
                [
                    'product_id' => $product2->id,
                    'unit_id' => $unit->id,
                    'quantity_adjusted' => -3,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product1->id,
        ]);
        assertDatabaseHas('stock_adjustment_items', [
            'stock_adjustment_id' => $adjustment->id,
            'product_id' => $product2->id,
        ]);
    });

    test('destroy cancels adjustment', function () {
        $adjustment = StockAdjustment::factory()->create(['status' => 'draft']);

        $response = deleteJson("/api/stock-adjustments/{$adjustment->id}");

        $response->assertNoContent();
        assertDatabaseHas('stock_adjustments', ['id' => $adjustment->id, 'status' => 'cancelled']);
    });
});
