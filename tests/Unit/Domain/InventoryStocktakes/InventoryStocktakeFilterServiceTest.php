<?php

use App\Domain\InventoryStocktakes\InventoryStocktakeFilterService;
use App\Models\InventoryStocktake;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('apply search filters by stocktake_number', function () {
    InventoryStocktake::factory()->create(['stocktake_number' => 'SO-MAIN-001', 'status' => 'draft']);
    InventoryStocktake::factory()->create(['stocktake_number' => 'SO-OTHER-001', 'status' => 'draft']);

    $service = new InventoryStocktakeFilterService();
    $query = InventoryStocktake::query();

    $service->applySearch($query, 'SO-MAIN', ['stocktake_number', 'notes']);

    expect($query->count())->toBe(1)
        ->and($query->first()->stocktake_number)->toBe('SO-MAIN-001');
});

test('applyAdvancedFilters filters by status', function () {
    InventoryStocktake::factory()->create(['status' => 'draft']);
    InventoryStocktake::factory()->create(['status' => 'completed']);

    $service = new InventoryStocktakeFilterService();
    $query = InventoryStocktake::query();

    $service->applyAdvancedFilters($query, ['status' => 'completed']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('completed');
});

