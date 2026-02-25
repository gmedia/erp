<?php

use App\Exports\WarehouseExport;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('warehouses');

describe('WarehouseExport', function () {

    test('query applies search filter', function () {
        Warehouse::factory()->create(['name' => 'UniqueWarehouseMain']);
        Warehouse::factory()->create(['name' => 'OtherWarehouseTransit']);

        $export = new WarehouseExport(['search' => 'UniqueWarehouse']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('UniqueWarehouseMain');
    });

    test('map function returns correct data', function () {
        $warehouse = Warehouse::factory()->make([
            'id' => 1,
            'name' => 'Test Warehouse',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new WarehouseExport([]);
        $mapped = $export->map($warehouse);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('Test Warehouse');
    });

    test('headings returns correct columns', function () {
        $export = new WarehouseExport([]);

        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
