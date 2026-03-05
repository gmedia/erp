<?php

use App\Models\StockTransferItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('stock-transfers');

test('factory creates a valid stock transfer item', function () {
    $item = StockTransferItem::factory()->create();

    assertDatabaseHas('stock_transfer_items', ['id' => $item->id]);
});

test('relationships are defined', function () {
    $item = StockTransferItem::factory()->create();

    expect($item->stockTransfer)->not->toBeNull()
        ->and($item->product)->not->toBeNull()
        ->and($item->unit)->not->toBeNull();
});

