<?php

use App\Domain\AssetStocktakes\AssetStocktakeVarianceQueryService;
use App\Models\Asset;
use App\Models\AssetStocktake;
use App\Models\AssetStocktakeItem;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('AssetStocktakeVarianceQueryService filters by stocktake and result', function () {
    $service = new AssetStocktakeVarianceQueryService;
    /** @var AssetStocktake $stocktakeA */
    $stocktakeA = AssetStocktake::factory()->create();
    /** @var AssetStocktake $stocktakeB */
    $stocktakeB = AssetStocktake::factory()->create();

    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktakeA->id,
        'result' => 'damaged',
    ]);
    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktakeB->id,
        'result' => 'missing',
    ]);

    $query = $service->buildBaseQuery();
    $service->applyFilters($query, [
        'asset_stocktake_id' => $stocktakeA->id,
        'result' => 'damaged',
    ]);

    expect($query->count())->toBe(1)
        ->and($query->first()->asset_stocktake_id)->toBe($stocktakeA->id)
        ->and($query->first()->result)->toBe('damaged');
});

test('AssetStocktakeVarianceQueryService filters by stocktake branch', function () {
    $service = new AssetStocktakeVarianceQueryService;
    /** @var Branch $branchA */
    $branchA = Branch::factory()->create();
    /** @var Branch $branchB */
    $branchB = Branch::factory()->create();
    /** @var AssetStocktake $stocktakeA */
    $stocktakeA = AssetStocktake::factory()->create(['branch_id' => $branchA->id]);
    /** @var AssetStocktake $stocktakeB */
    $stocktakeB = AssetStocktake::factory()->create(['branch_id' => $branchB->id]);

    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktakeA->id,
        'result' => 'damaged',
    ]);
    AssetStocktakeItem::factory()->create([
        'asset_stocktake_id' => $stocktakeB->id,
        'result' => 'missing',
    ]);

    $query = $service->buildBaseQuery();
    $service->applyFilters($query, ['branch_id' => $branchA->id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->stocktake->branch_id)->toBe($branchA->id);
});

test('AssetStocktakeVarianceQueryService searches by asset fields', function () {
    $service = new AssetStocktakeVarianceQueryService;
    /** @var Asset $asset */
    $asset = Asset::factory()->create([
        'asset_code' => 'FA-SEARCH-001',
        'name' => 'Variance Search Asset',
    ]);

    AssetStocktakeItem::factory()->create([
        'asset_id' => $asset->id,
        'result' => 'damaged',
    ]);
    AssetStocktakeItem::factory()->create([
        'result' => 'missing',
    ]);

    $query = $service->buildBaseQuery();
    $service->applyFilters($query, ['search' => 'FA-SEARCH']);

    expect($query->count())->toBe(1)
        ->and($query->first()->asset->asset_code)->toBe('FA-SEARCH-001');
});
