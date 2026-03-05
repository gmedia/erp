<?php

use App\Domain\StockAdjustments\StockAdjustmentFilterService;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('apply search filters by adjustment_number', function () {
    StockAdjustment::factory()->create(['adjustment_number' => 'SA-MAIN-001', 'status' => 'draft']);
    StockAdjustment::factory()->create(['adjustment_number' => 'SA-OTHER-001', 'status' => 'draft']);

    $service = new StockAdjustmentFilterService();
    $query = StockAdjustment::query();

    $service->applySearch($query, 'SA-MAIN', ['adjustment_number', 'notes']);

    expect($query->count())->toBe(1)
        ->and($query->first()->adjustment_number)->toBe('SA-MAIN-001');
});

test('applyAdvancedFilters filters by status', function () {
    StockAdjustment::factory()->create(['status' => 'draft']);
    StockAdjustment::factory()->create(['status' => 'approved']);

    $service = new StockAdjustmentFilterService();
    $query = StockAdjustment::query();

    $service->applyAdvancedFilters($query, ['status' => 'approved']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('approved');
});
