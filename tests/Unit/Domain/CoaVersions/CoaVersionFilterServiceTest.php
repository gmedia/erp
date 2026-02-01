<?php

use App\Domain\CoaVersions\CoaVersionFilterService;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('coa-versions');

beforeEach(function () {
    $this->service = new CoaVersionFilterService();
});

test('applySearch filters by name', function () {
    CoaVersion::factory()->create(['name' => 'Alpha']);
    CoaVersion::factory()->create(['name' => 'Beta']);

    $query = CoaVersion::query();
    $this->service->applySearch($query, 'Alpha', ['name']);

    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Alpha');
});

test('applyAdvancedFilters filters by status and fiscal year', function () {
    $fy1 = FiscalYear::factory()->create();
    $fy2 = FiscalYear::factory()->create();

    CoaVersion::factory()->create(['status' => 'draft', 'fiscal_year_id' => $fy1->id]);
    CoaVersion::factory()->create(['status' => 'active', 'fiscal_year_id' => $fy2->id]);

    // Test status filter
    $query = CoaVersion::query();
    $this->service->applyAdvancedFilters($query, ['status' => 'draft']);
    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('draft');

    // Test fiscal year filter
    $query = CoaVersion::query();
    $this->service->applyAdvancedFilters($query, ['fiscal_year_id' => $fy1->id]);
    expect($query->count())->toBe(1)
        ->and($query->first()->fiscal_year_id)->toBe($fy1->id);
        
    // Test combined filters
    $query = CoaVersion::query();
    $this->service->applyAdvancedFilters($query, ['status' => 'active', 'fiscal_year_id' => $fy2->id]);
    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('active')
        ->and($query->first()->fiscal_year_id)->toBe($fy2->id);
});

test('applySorting orders results correctly', function () {
    CoaVersion::factory()->create(['name' => 'B']);
    CoaVersion::factory()->create(['name' => 'A']);

    $query = CoaVersion::query();
    $this->service->applySorting($query, 'name', 'asc', ['name']);

    expect($query->first()->name)->toBe('A');
});
