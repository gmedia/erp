<?php

use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('stock-transfers');

test('factory creates a valid stock transfer', function () {
    $transfer = StockTransfer::factory()->create();

    assertDatabaseHas('stock_transfers', ['id' => $transfer->id]);
});

test('relationships are defined', function () {
    $transfer = StockTransfer::factory()->create();

    expect($transfer->fromWarehouse)->not->toBeNull()
        ->and($transfer->toWarehouse)->not->toBeNull();
});

