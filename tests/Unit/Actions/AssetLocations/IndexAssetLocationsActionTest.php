<?php

use App\Actions\AssetLocations\IndexAssetLocationsAction;
use App\Domain\AssetLocations\AssetLocationFilterService;
use App\Http\Requests\AssetLocations\IndexAssetLocationRequest;
use App\Models\AssetLocation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-locations', 'actions');

test('execute returns paginated results', function () {
    AssetLocation::factory()->count(3)->create();

    $action = new IndexAssetLocationsAction(new AssetLocationFilterService());
    $request = new IndexAssetLocationRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('execute filters by search term', function () {
    AssetLocation::factory()->create(['name' => 'Warehouse A']);
    AssetLocation::factory()->create(['name' => 'Office B']);

    $action = new IndexAssetLocationsAction(new AssetLocationFilterService());
    $request = new IndexAssetLocationRequest(['search' => 'Warehouse']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->name)->toBe('Warehouse A');
});

test('execute filters by branch_id', function () {
    $location1 = AssetLocation::factory()->create();
    AssetLocation::factory()->count(2)->create();

    $action = new IndexAssetLocationsAction(new AssetLocationFilterService());
    $request = new IndexAssetLocationRequest(['branch_id' => $location1->branch_id]);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->id)->toBe($location1->id);
});
