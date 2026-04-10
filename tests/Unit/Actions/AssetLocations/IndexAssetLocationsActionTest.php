<?php

use App\Actions\AssetLocations\IndexAssetLocationsAction;
use App\Domain\AssetLocations\AssetLocationFilterService;
use App\Http\Requests\AssetLocations\IndexAssetLocationRequest;
use App\Models\AssetLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('asset-locations');

test('execute returns paginated results', function () {
    AssetLocation::factory()->count(3)->create();

    $action = new IndexAssetLocationsAction(new AssetLocationFilterService);
    $request = new IndexAssetLocationRequest;

    $result = $action->execute($request);
    $items = $result->items();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($items)->toHaveCount(3);
});

test('execute filters by search term', function () {
    AssetLocation::factory()->create(['name' => 'Warehouse A']);
    AssetLocation::factory()->create(['name' => 'Office B']);

    $action = new IndexAssetLocationsAction(new AssetLocationFilterService);
    $request = new IndexAssetLocationRequest(['search' => 'Warehouse']);

    $result = $action->execute($request);
    $items = $result->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->name)->toBe('Warehouse A');
});

test('execute filters by branch_id', function () {
    $location1 = AssetLocation::factory()->create();
    AssetLocation::factory()->count(2)->create();

    $action = new IndexAssetLocationsAction(new AssetLocationFilterService);
    $request = new IndexAssetLocationRequest(['branch_id' => $location1->branch_id]);

    $result = $action->execute($request);
    $items = $result->items();

    expect($items)->toHaveCount(1)
        ->and($items[0]->id)->toBe($location1->id);
});

test('execute sorts by parent name', function () {
    $parentA = AssetLocation::factory()->create(['name' => 'A Parent']);
    $parentB = AssetLocation::factory()->create(['name' => 'B Parent']);

    AssetLocation::factory()->create(['name' => 'Target B', 'parent_id' => $parentB->id]);
    AssetLocation::factory()->create(['name' => 'Target A', 'parent_id' => $parentA->id]);

    $action = new IndexAssetLocationsAction(new AssetLocationFilterService);
    $request = new IndexAssetLocationRequest([
        'search' => 'Target',
        'sort_by' => 'parent',
        'sort_direction' => 'asc',
    ]);

    $result = $action->execute($request);
    $items = $result->items();

    expect($items)->toHaveCount(2)
        ->and($items[0]->parent_id)->toBe($parentA->id)
        ->and($items[1]->parent_id)->toBe($parentB->id);
});
