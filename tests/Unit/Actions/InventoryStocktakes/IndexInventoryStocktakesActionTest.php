<?php

use App\Actions\InventoryStocktakes\IndexInventoryStocktakesAction;
use App\Domain\InventoryStocktakes\InventoryStocktakeFilterService;
use App\Http\Requests\InventoryStocktakes\IndexInventoryStocktakeRequest;
use App\Models\InventoryStocktake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

test('execute returns paginated results', function () {
    InventoryStocktake::factory()->count(3)->create(['status' => 'draft']);

    $action = new IndexInventoryStocktakesAction(new InventoryStocktakeFilterService);
    $request = new IndexInventoryStocktakeRequest;

    $result = $action->execute($request);
    $items = $result->items();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($items)->toHaveCount(3);
});

test('execute filters by search term', function () {
    InventoryStocktake::factory()->create(['stocktake_number' => 'SO-ABC-001', 'status' => 'draft']);
    InventoryStocktake::factory()->create(['stocktake_number' => 'SO-XYZ-001', 'status' => 'draft']);

    $action = new IndexInventoryStocktakesAction(new InventoryStocktakeFilterService);
    $request = new IndexInventoryStocktakeRequest(['search' => 'SO-ABC']);

    $result = $action->execute($request);
    $items = $result->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->stocktake_number)->toBe('SO-ABC-001');
});

test('execute excludes cancelled by default', function () {
    InventoryStocktake::factory()->create(['status' => 'cancelled']);
    InventoryStocktake::factory()->create(['status' => 'draft']);

    $action = new IndexInventoryStocktakesAction(new InventoryStocktakeFilterService);
    $request = new IndexInventoryStocktakeRequest;

    $result = $action->execute($request);
    $items = $result->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->status)->toBe('draft');
});

test('execute can include cancelled when status filter set', function () {
    InventoryStocktake::factory()->create(['status' => 'cancelled']);

    $action = new IndexInventoryStocktakesAction(new InventoryStocktakeFilterService);
    $request = new IndexInventoryStocktakeRequest(['status' => 'cancelled']);

    $result = $action->execute($request);
    $items = $result->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->status)->toBe('cancelled');
});
