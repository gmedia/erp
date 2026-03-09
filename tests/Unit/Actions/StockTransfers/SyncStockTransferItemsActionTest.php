<?php

use App\Actions\StockTransfers\SyncStockTransferItemsAction;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class)->group('stock-transfers');

test('execute upserts items and removes missing ones', function () {
    $transfer = StockTransfer::factory()->create(['status' => 'draft']);
    $unit = Unit::factory()->create();
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    StockTransferItem::factory()->create([
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product1->id,
        'unit_id' => $unit->id,
        'quantity' => 1,
    ]);

    $action = new SyncStockTransferItemsAction;
    $action->execute($transfer, [
        [
            'product_id' => $product2->id,
            'unit_id' => $unit->id,
            'quantity' => 5,
            'quantity_received' => 0,
            'unit_cost' => 10,
            'notes' => null,
        ],
    ]);

    assertDatabaseHas('stock_transfer_items', [
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product2->id,
        'quantity' => 5,
    ]);

    assertDatabaseMissing('stock_transfer_items', [
        'stock_transfer_id' => $transfer->id,
        'product_id' => $product1->id,
    ]);
});
