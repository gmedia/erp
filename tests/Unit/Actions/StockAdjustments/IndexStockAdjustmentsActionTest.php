<?php

use App\Actions\StockAdjustments\IndexStockAdjustmentsAction;
use App\Domain\StockAdjustments\StockAdjustmentFilterService;
use App\Http\Requests\StockAdjustments\IndexStockAdjustmentRequest;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('execute returns paginated results', function () {
    StockAdjustment::factory()->count(3)->create(['status' => 'draft']);

    $action = new IndexStockAdjustmentsAction(new StockAdjustmentFilterService());
    $request = new IndexStockAdjustmentRequest();

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    StockAdjustment::factory()->create(['adjustment_number' => 'SA-ABC-001', 'status' => 'draft']);
    StockAdjustment::factory()->create(['adjustment_number' => 'SA-XYZ-001', 'status' => 'draft']);

    $action = new IndexStockAdjustmentsAction(new StockAdjustmentFilterService());
    $request = new IndexStockAdjustmentRequest(['search' => 'SA-ABC']);

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->adjustment_number)->toBe('SA-ABC-001');
});

test('execute excludes cancelled by default', function () {
    StockAdjustment::factory()->create(['status' => 'cancelled']);
    StockAdjustment::factory()->create(['status' => 'draft']);

    $action = new IndexStockAdjustmentsAction(new StockAdjustmentFilterService());
    $request = new IndexStockAdjustmentRequest();

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->status)->toBe('draft');
});

test('execute can include cancelled when status filter set', function () {
    StockAdjustment::factory()->create(['status' => 'cancelled']);

    $action = new IndexStockAdjustmentsAction(new StockAdjustmentFilterService());
    $request = new IndexStockAdjustmentRequest(['status' => 'cancelled']);

    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->status)->toBe('cancelled');
});
