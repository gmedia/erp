<?php

use App\Domain\AssetStocktakes\AssetStocktakeFilterService;
use App\Models\AssetStocktake;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('it filters by branch', function () {
    $branch1 = Branch::factory()->create();
    $branch2 = Branch::factory()->create();

    AssetStocktake::factory()->create(['branch_id' => $branch1->id]);
    AssetStocktake::factory()->create(['branch_id' => $branch2->id]);

    $service = new AssetStocktakeFilterService();
    $query = AssetStocktake::query();

    $service->applyAdvancedFilters($query, ['branch_id' => $branch1->id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->branch_id)->toBe($branch1->id);
});

test('it filters by status', function () {
    AssetStocktake::factory()->create(['status' => 'draft']);
    AssetStocktake::factory()->create(['status' => 'completed']);

    $service = new AssetStocktakeFilterService();
    $query = AssetStocktake::query();

    $service->applyAdvancedFilters($query, ['status' => 'draft']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('draft');
});

test('it filters by planned_at range', function () {
    AssetStocktake::factory()->create(['planned_at' => '2024-01-01']);
    AssetStocktake::factory()->create(['planned_at' => '2024-02-01']);
    AssetStocktake::factory()->create(['planned_at' => '2024-03-01']);

    $service = new AssetStocktakeFilterService();
    $query = AssetStocktake::query();

    $service->applyAdvancedFilters($query, [
        'planned_at_from' => '2024-01-15',
        'planned_at_to' => '2024-02-15',
    ]);

    expect($query->count())->toBe(1)
        ->and($query->first()->planned_at->format('Y-m-d'))->toBe('2024-02-01');
});
