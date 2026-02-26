<?php

use App\Actions\StockTransfers\IndexStockTransfersAction;
use App\Domain\StockTransfers\StockTransferFilterService;
use App\Http\Requests\StockTransfers\IndexStockTransferRequest;
use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('stock-transfers');

test('execute returns paginated results', function () {
    StockTransfer::factory()->count(3)->create(['status' => 'draft']);

    $action = new IndexStockTransfersAction(new StockTransferFilterService());
    $request = new IndexStockTransferRequest();

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    StockTransfer::factory()->create(['transfer_number' => 'ST-ABC-001', 'status' => 'draft']);
    StockTransfer::factory()->create(['transfer_number' => 'ST-XYZ-001', 'status' => 'draft']);

    $action = new IndexStockTransfersAction(new StockTransferFilterService());
    $request = new IndexStockTransferRequest(['search' => 'ST-ABC']);

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->transfer_number)->toBe('ST-ABC-001');
});

test('execute excludes cancelled by default', function () {
    StockTransfer::factory()->create(['status' => 'cancelled']);
    StockTransfer::factory()->create(['status' => 'draft']);

    $action = new IndexStockTransfersAction(new StockTransferFilterService());
    $request = new IndexStockTransferRequest();

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->status)->toBe('draft');
});

test('execute can include cancelled when status filter set', function () {
    StockTransfer::factory()->create(['status' => 'cancelled']);

    $action = new IndexStockTransfersAction(new StockTransferFilterService());
    $request = new IndexStockTransferRequest(['status' => 'cancelled']);

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->status)->toBe('cancelled');
});

