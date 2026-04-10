<?php

use App\Domain\AssetDepreciationRuns\AssetDepreciationRunFilterService;
use App\Models\AssetDepreciationRun;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-depreciation-runs');

test('applyAdvancedFilters applies fiscal year filter', function () {
    $service = new AssetDepreciationRunFilterService;
    $fiscalYearA = FiscalYear::factory()->create();
    $fiscalYearB = FiscalYear::factory()->create();

    AssetDepreciationRun::factory()->create(['fiscal_year_id' => $fiscalYearA->id]);
    AssetDepreciationRun::factory()->create(['fiscal_year_id' => $fiscalYearB->id]);

    $query = AssetDepreciationRun::query();
    $service->applyAdvancedFilters($query, ['fiscal_year_id' => $fiscalYearA->id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->fiscal_year_id)->toBe($fiscalYearA->id);
});

test('applyAdvancedFilters applies status filter', function () {
    $service = new AssetDepreciationRunFilterService;

    AssetDepreciationRun::factory()->create(['status' => 'draft']);
    AssetDepreciationRun::factory()->create(['status' => 'posted']);

    $query = AssetDepreciationRun::query();
    $service->applyAdvancedFilters($query, ['status' => 'posted']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('posted');
});

test('applyAdvancedFilters applies date bounds', function () {
    $service = new AssetDepreciationRunFilterService;

    AssetDepreciationRun::factory()->create([
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
    ]);
    AssetDepreciationRun::factory()->create([
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
    ]);

    $query = AssetDepreciationRun::query();
    $service->applyAdvancedFilters($query, [
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-29',
    ]);

    expect($query->count())->toBe(1)
        ->and($query->first()->period_start->format('Y-m-d'))->toBe('2024-02-01');
});
