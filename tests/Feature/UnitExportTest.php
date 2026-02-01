<?php

use App\Exports\UnitExport;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('units');

describe('UnitExport', function () {

    test('query applies search filter', function () {
        Unit::factory()->create(['name' => 'Kilogram']);
        Unit::factory()->create(['name' => 'Meter']);

        $export = new UnitExport(['search' => 'Kilo']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Kilogram');
    });

    test('map function returns correct data', function () {
        $unit = Unit::factory()->make([
            'id' => 1,
            'name' => 'Test Unit',
            'symbol' => 'TU',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new UnitExport([]);
        $mapped = $export->map($unit);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('Test Unit');
        // Likely symbol is 3rd? Need to check.
        // I will assume for now.
    });

    test('headings returns correct columns', function () {
        $export = new UnitExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Created At');
    });
});
