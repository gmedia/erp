<?php

use App\Exports\StockAdjustmentExport;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('stock-adjustments');

describe('StockAdjustmentExport', function () {
    test('query applies search filter', function () {
        StockAdjustment::factory()->create(['adjustment_number' => 'SA-UNIQUE-001', 'status' => 'draft']);
        StockAdjustment::factory()->create(['adjustment_number' => 'SA-OTHER-001', 'status' => 'draft']);

        $export = new StockAdjustmentExport(['search' => 'SA-UNIQUE']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->adjustment_number)->toBe('SA-UNIQUE-001');
    });

    test('map function returns correct data', function () {
        $adjustment = StockAdjustment::factory()->make([
            'id' => 1,
            'adjustment_number' => 'SA-TEST-0001',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new StockAdjustmentExport([]);
        $mapped = $export->map($adjustment);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('SA-TEST-0001');
    });

    test('headings returns correct columns', function () {
        $export = new StockAdjustmentExport([]);

        expect($export->headings())->toContain(
            'ID',
            'Adjustment Number',
            'Warehouse',
            'Adjustment Date',
            'Adjustment Type',
            'Status',
            'Stocktake Number',
            'Created At',
        );
    });
});
