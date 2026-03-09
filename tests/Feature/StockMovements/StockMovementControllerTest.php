<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('stock-movements');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['stock_movement']);
    $this->otherUser = createTestUserWithPermissions([]);
});

test('it requires permission to access stock movements', function () {
    \Laravel\Sanctum\Sanctum::actingAs($this->otherUser, ['*']);
    getJson('/api/stock-movements')
        ->assertForbidden();
});

test('it can fetch stock movements data via json', function () {
    $product = Product::factory()->create(['name' => 'Kertas A4', 'code' => 'P-001']);
    $warehouse = Warehouse::factory()->create(['name' => 'Gudang Utama', 'code' => 'WH-001']);

    StockMovement::factory()->create([
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'movement_type' => 'adjustment_in',
        'quantity_in' => 10,
        'quantity_out' => 0,
        'balance_after' => 10,
        'reference_number' => 'SA-2026-000001',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    getJson('/api/stock-movements?per_page=10')
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('data', 1)
            ->has('data.0', fn (AssertableJson $m) => $m
                ->where('movement_type', 'adjustment_in')
                ->where('reference_number', 'SA-2026-000001')
                ->where('product.name', 'Kertas A4')
                ->where('warehouse.name', 'Gudang Utama')
                ->etc()
            )
            ->has('meta')
            ->has('links')
        );
});

test('it can filter by product, warehouse, movement_type, and date range', function () {
    $p1 = Product::factory()->create(['name' => 'Produk A']);
    $p2 = Product::factory()->create(['name' => 'Produk B']);
    $w1 = Warehouse::factory()->create(['name' => 'WH A']);
    $w2 = Warehouse::factory()->create(['name' => 'WH B']);

    StockMovement::factory()->create([
        'product_id' => $p1->id,
        'warehouse_id' => $w1->id,
        'movement_type' => 'transfer_in',
        'moved_at' => '2026-01-10 10:00:00',
    ]);

    StockMovement::factory()->create([
        'product_id' => $p2->id,
        'warehouse_id' => $w2->id,
        'movement_type' => 'transfer_out',
        'moved_at' => '2025-01-01 10:00:00',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    getJson('/api/stock-movements?product_id=' . $p1->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.product.id', $p1->id);

    getJson('/api/stock-movements?warehouse_id=' . $w1->id)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.warehouse.id', $w1->id);

    getJson('/api/stock-movements?movement_type=transfer_in')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.movement_type', 'transfer_in');

    getJson('/api/stock-movements?start_date=2026-01-01&end_date=2026-12-31')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.movement_type', 'transfer_in');
});

test('it can sort by product name', function () {
    $pA = Product::factory()->create(['name' => 'AAA']);
    $pB = Product::factory()->create(['name' => 'BBB']);
    $w = Warehouse::factory()->create();

    StockMovement::factory()->create(['product_id' => $pB->id, 'warehouse_id' => $w->id, 'moved_at' => '2026-01-01 00:00:00']);
    StockMovement::factory()->create(['product_id' => $pA->id, 'warehouse_id' => $w->id, 'moved_at' => '2026-01-01 00:00:00']);

    \Laravel\Sanctum\Sanctum::actingAs($this->user, ['*']);
    $response = getJson('/api/stock-movements?sort_by=product_name&sort_direction=asc')
        ->assertOk();

    expect($response->json('data.0.product.name'))->toBe('AAA');
    expect($response->json('data.1.product.name'))->toBe('BBB');
});
