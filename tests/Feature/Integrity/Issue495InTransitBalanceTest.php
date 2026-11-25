<?php

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class)->group('stock-monitor');

/**
 * Transfer berstatus $status dengan satu item $quantity @ $unitCost.
 */
function issue495TransferInTransit(Warehouse $from, Warehouse $to, Product $product, float $quantity, float $unitCost, string $status = 'in_transit'): StockTransfer
{
    $transfer = StockTransfer::factory()->create([
        'from_warehouse_id' => $from->id,
        'to_warehouse_id' => $to->id,
        'transfer_date' => '2026-01-13',
        'status' => $status,
    ]);

    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'quantity_received' => null,
        'unit_cost' => $unitCost,
    ]);

    return $transfer;
}

test('stock monitor menampilkan saldo in-transit per transfer belum diterima', function () {
    $source = Warehouse::factory()->create(['name' => 'Gudang Asal 495']);
    $destination = Warehouse::factory()->create(['name' => 'Gudang Tujuan 495']);
    $product = Product::factory()->create(['cost' => 0]);

    issue495TransferInTransit($source, $destination, $product, 4, 1500);
    issue495TransferInTransit($source, $destination, $product, 2, 1000, 'received');

    Sanctum::actingAs(createTestUserWithPermissions(['stock_monitor']), ['*']);

    $response = $this->getJson('/api/stock-monitor?per_page=10')
        ->assertOk();

    expect($response->json('summary.in_transit.total_items'))->toBe(1)
        ->and($response->json('summary.in_transit.total_quantity'))->toBe('4.00')
        ->and($response->json('summary.in_transit.total_value'))->toBe('6000.00')
        ->and($response->json('summary.in_transit.rows.0.transfer_number'))->not->toBeNull()
        ->and($response->json('summary.in_transit.rows.0.from_warehouse'))->toBe('Gudang Asal 495')
        ->and($response->json('summary.in_transit.rows.0.to_warehouse'))->toBe('Gudang Tujuan 495')
        ->and($response->json('summary.in_transit.rows.0.quantity'))->toBe('4.00')
        ->and($response->json('summary.in_transit.rows.0.unit_cost'))->toBe('1500.00')
        ->and($response->json('summary.in_transit.rows.0.value'))->toBe('6000.00');
});

test('laporan valuasi menampilkan baris in-transit per transfer belum diterima', function () {
    $source = Warehouse::factory()->create(['name' => 'Gudang Kirim 495']);
    $destination = Warehouse::factory()->create(['name' => 'Gudang Terima 495']);
    $product = Product::factory()->create(['cost' => 0]);

    issue495TransferInTransit($source, $destination, $product, 3, 2000);

    Sanctum::actingAs(createTestUserWithPermissions(['inventory_valuation_report']), ['*']);

    $response = $this->getJson('/api/reports/inventory-valuation')
        ->assertOk();

    expect($response->json('meta.in_transit.total_value'))->toBe('6000.00')
        ->and($response->json('meta.in_transit.total_items'))->toBe(1)
        ->and($response->json('meta.in_transit.rows.0.from_warehouse'))->toBe('Gudang Kirim 495')
        ->and($response->json('meta.in_transit.rows.0.value'))->toBe('6000.00');
});

test('transfer belum diterima dengan status in_transit tanpa item tidak menambah saldo', function () {
    $source = Warehouse::factory()->create();
    $destination = Warehouse::factory()->create();

    StockTransfer::factory()->create([
        'from_warehouse_id' => $source->id,
        'to_warehouse_id' => $destination->id,
        'transfer_date' => '2026-01-13',
        'status' => 'in_transit',
    ]);

    Sanctum::actingAs(createTestUserWithPermissions(['stock_monitor']), ['*']);

    $this->getJson('/api/stock-monitor?per_page=10')
        ->assertOk()
        ->assertJsonPath('summary.in_transit.total_value', '0.00')
        ->assertJsonPath('summary.in_transit.total_items', 0);
});
