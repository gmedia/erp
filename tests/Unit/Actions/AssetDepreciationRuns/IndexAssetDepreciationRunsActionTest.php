<?php

use App\Actions\AssetDepreciationRuns\IndexAssetDepreciationRunsAction;
use App\Domain\AssetDepreciationRuns\AssetDepreciationRunFilterService;
use App\Http\Requests\AssetDepreciationRuns\IndexAssetDepreciationRunRequest;
use App\Models\AssetDepreciationRun;
use App\Models\FiscalYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-depreciation-runs');

beforeEach(function () {
    $this->action = new IndexAssetDepreciationRunsAction(new AssetDepreciationRunFilterService);
});

test('it paginates filtered asset depreciation runs', function () {
    $matchingFiscalYear = FiscalYear::factory()->create();
    $otherFiscalYear = FiscalYear::factory()->create();

    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $matchingFiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
        'status' => 'calculated',
    ]);
    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $matchingFiscalYear->id,
        'period_start' => '2024-01-01',
        'period_end' => '2024-01-31',
        'status' => 'draft',
    ]);
    AssetDepreciationRun::factory()->create([
        'fiscal_year_id' => $otherFiscalYear->id,
        'period_start' => '2024-02-01',
        'period_end' => '2024-02-29',
        'status' => 'calculated',
    ]);

    $result = $this->action->execute(new IndexAssetDepreciationRunRequest([
        'fiscal_year_id' => $matchingFiscalYear->id,
        'start_date' => '2024-02-01',
        'end_date' => '2024-02-29',
        'status' => 'calculated',
        'sort_by' => 'period_start',
        'sort_direction' => 'asc',
        'per_page' => 10,
        'page' => 1,
    ]));

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(1)
        ->and($result->items())->toHaveCount(1)
        ->and($result->items()[0]->fiscal_year_id)->toBe($matchingFiscalYear->id)
        ->and($result->items()[0]->status)->toBe('calculated')
        ->and($result->items()[0]->relationLoaded('fiscalYear'))->toBeTrue();
});
