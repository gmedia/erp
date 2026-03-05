<?php

use App\Exports\StockTransferExport;
use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('stock-transfers');

describe('StockTransferExport', function () {
    test('query applies search filter', function () {
        StockTransfer::factory()->create(['transfer_number' => 'ST-UNIQUE-001', 'status' => 'draft']);
        StockTransfer::factory()->create(['transfer_number' => 'ST-OTHER-001', 'status' => 'draft']);

        $export = new StockTransferExport(['search' => 'ST-UNIQUE']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->transfer_number)->toBe('ST-UNIQUE-001');
    });

    test('map function returns correct data', function () {
        $transfer = StockTransfer::factory()->make([
            'id' => 1,
            'transfer_number' => 'ST-TEST-0001',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new StockTransferExport([]);
        $mapped = $export->map($transfer);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('ST-TEST-0001');
    });

    test('headings returns correct columns', function () {
        $export = new StockTransferExport([]);

        expect($export->headings())->toContain(
            'ID',
            'Transfer Number',
            'From Warehouse',
            'To Warehouse',
            'Transfer Date',
            'Expected Arrival Date',
            'Status',
            'Created At',
        );
    });
});

