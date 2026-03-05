<?php

use App\Domain\StockTransfers\StockTransferFilterService;
use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('stock-transfers');

test('apply search filters by transfer_number', function () {
    StockTransfer::factory()->create(['transfer_number' => 'ST-MAIN-001', 'status' => 'draft']);
    StockTransfer::factory()->create(['transfer_number' => 'ST-OTHER-001', 'status' => 'draft']);

    $service = new StockTransferFilterService();
    $query = StockTransfer::query();

    $service->applySearch($query, 'ST-MAIN', ['transfer_number', 'notes']);

    expect($query->count())->toBe(1)
        ->and($query->first()->transfer_number)->toBe('ST-MAIN-001');
});

test('applyAdvancedFilters filters by status', function () {
    StockTransfer::factory()->create(['status' => 'draft']);
    StockTransfer::factory()->create(['status' => 'approved']);

    $service = new StockTransferFilterService();
    $query = StockTransfer::query();

    $service->applyAdvancedFilters($query, ['status' => 'approved']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('approved');
});

