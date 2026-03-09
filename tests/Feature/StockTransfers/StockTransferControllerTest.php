<?php

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('stock-transfers');

describe('Stock Transfer API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'stock_transfer',
            'stock_transfer.create',
            'stock_transfer.edit',
            'stock_transfer.delete',
        ]);

        actingAs($user);
    });

    test('index returns paginated transfers', function () {
        StockTransfer::factory()->count(3)->create(['status' => 'draft']);

        $response = getJson('/api/stock-transfers?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'transfer_number',
                        'from_warehouse',
                        'to_warehouse',
                        'transfer_date',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => ['total', 'per_page', 'current_page'],
            ]);
    });

    test('index excludes cancelled transfers by default', function () {
        StockTransfer::factory()->create(['status' => 'cancelled']);
        StockTransfer::factory()->create(['status' => 'draft']);

        $response = getJson('/api/stock-transfers');
        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.status'))->toBe('draft');
    });

    test('index can include cancelled transfers when filtered by status', function () {
        StockTransfer::factory()->create(['status' => 'cancelled']);

        $response = getJson('/api/stock-transfers?status=cancelled');
        $response->assertOk();

        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.status'))->toBe('cancelled');
    });

    test('store creates transfer with items', function () {
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $response = postJson('/api/stock-transfers', [
            'transfer_number' => 'ST-TEST-0001',
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'transfer_date' => now()->toDateString(),
            'expected_arrival_date' => null,
            'status' => 'draft',
            'notes' => 'Test notes',
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity' => 2,
                    'quantity_received' => 0,
                    'unit_cost' => 100,
                    'notes' => null,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['transfer_number' => 'ST-TEST-0001']);

        assertDatabaseHas('stock_transfers', [
            'transfer_number' => 'ST-TEST-0001',
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'status' => 'draft',
        ]);

        assertDatabaseHas('stock_transfer_items', [
            'product_id' => $product->id,
            'unit_id' => $unit->id,
        ]);
    });

    test('store auto-generates transfer_number when empty', function () {
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();

        $response = postJson('/api/stock-transfers', [
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'transfer_date' => now()->toDateString(),
            'expected_arrival_date' => null,
            'status' => 'draft',
            'notes' => null,
            'items' => [
                [
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertCreated();

        $transferNumber = $response->json('data.transfer_number');
        expect($transferNumber)->toBeString()->and($transferNumber)->toStartWith('ST-');
    });

    test('update modifies transfer and syncs items', function () {
        $from = Warehouse::factory()->create();
        $to = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        $unit = Unit::factory()->create();

        $transfer = StockTransfer::factory()->create([
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'status' => 'draft',
        ]);

        $response = putJson("/api/stock-transfers/{$transfer->id}", [
            'status' => 'approved',
            'items' => [
                [
                    'product_id' => $product1->id,
                    'unit_id' => $unit->id,
                    'quantity' => 5,
                ],
                [
                    'product_id' => $product2->id,
                    'unit_id' => $unit->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonFragment(['status' => 'approved']);

        assertDatabaseHas('stock_transfer_items', [
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product1->id,
        ]);
        assertDatabaseHas('stock_transfer_items', [
            'stock_transfer_id' => $transfer->id,
            'product_id' => $product2->id,
        ]);
    });

    test('destroy cancels transfer', function () {
        $transfer = StockTransfer::factory()->create(['status' => 'draft']);

        $response = deleteJson("/api/stock-transfers/{$transfer->id}");

        $response->assertNoContent();
        assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'status' => 'cancelled']);
    });
});
