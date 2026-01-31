<?php

use App\Exports\FiscalYearExport;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('fiscal-years');

describe('FiscalYearExport', function () {
    test('headings returns correct column headers', function () {
        $export = new FiscalYearExport([]);
        $headings = $export->headings();

        expect($headings)->toBe(['ID', 'Name', 'Start Date', 'End Date', 'Status', 'Created At', 'Updated At']);
    });

    test('map returns correct data structure', function () {
        $fiscalYear = FiscalYear::factory()->make([
            'id' => 1,
            'name' => 'FY 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $export = new FiscalYearExport([]);
        $mapped = $export->map($fiscalYear);

        expect($mapped)->toHaveCount(7)
            ->and($mapped[0])->toBe(1)
            ->and($mapped[1])->toBe('FY 2025')
            ->and($mapped[2])->toBe('2025-01-01')
            ->and($mapped[3])->toBe('2025-12-31')
            ->and($mapped[4])->toBe('open');
    });

    test('map handles null values gracefully', function () {
        $fiscalYear = new FiscalYear([
            'name' => 'Partial FY',
            'status' => 'open'
        ]);

        $export = new FiscalYearExport([]);
        $mapped = $export->map($fiscalYear);

        expect($mapped[2])->toBeNull()
            ->and($mapped[3])->toBeNull()
            ->and($mapped[5])->toBeNull()
            ->and($mapped[6])->toBeNull();
    });
});
