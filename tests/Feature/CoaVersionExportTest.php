<?php

use App\Exports\CoaVersionExport;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('coa-versions');

describe('CoaVersionExport', function () {
    beforeEach(function () {
        $this->fiscalYear = FiscalYear::factory()->create(['name' => 'UniqueFY2026']);
    });

    test('query applies search filter', function () {
        CoaVersion::factory()->create(['name' => 'UniqueCoaVersionName', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'OtherVersionName', 'fiscal_year_id' => $this->fiscalYear->id]);

        $export = new CoaVersionExport(['search' => 'UniqueCoaVersion']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('UniqueCoaVersionName');
    });

    test('query applies status filter', function () {
        // Use a unique name to avoid overlap with seeders
        CoaVersion::factory()->create(['name' => 'StatusActiveTest', 'status' => 'active', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'StatusDraftTest', 'status' => 'draft', 'fiscal_year_id' => $this->fiscalYear->id]);

        $export = new CoaVersionExport(['status' => 'active', 'search' => 'StatusActiveTest']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->status)->toBe('active');
    });

    test('query applies fiscal year filter', function () {
        $otherFY = FiscalYear::factory()->create(['name' => 'OtherUniqueFY']);
        CoaVersion::factory()->create(['name' => 'CoaFY1', 'fiscal_year_id' => $this->fiscalYear->id]);
        CoaVersion::factory()->create(['name' => 'CoaFY2', 'fiscal_year_id' => $otherFY->id]);

        $export = new CoaVersionExport(['fiscal_year_id' => $this->fiscalYear->id, 'search' => 'CoaFY1']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->fiscal_year_id)->toBe($this->fiscalYear->id);
    });

    test('map function returns correct data', function () {
        $version = CoaVersion::factory()->create([
            'name' => 'Standard COA Map Test',
            'fiscal_year_id' => $this->fiscalYear->id,
            'status' => 'active',
            'created_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 12:00:00',
        ]);

        $export = new CoaVersionExport([]);
        $mapped = $export->map($version);

        expect($mapped)->toBeArray()
            ->and($mapped[0])->toBe($version->id)
            ->and($mapped[1])->toBe('Standard COA Map Test')
            ->and($mapped[2])->toBe('UniqueFY2026')
            ->and($mapped[3])->toBe('active');
    });

    test('headings returns correct columns', function () {
        $export = new CoaVersionExport([]);
        
        expect($export->headings())->toContain('ID', 'Name', 'Fiscal Year', 'Status');
    });
});
