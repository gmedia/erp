<?php

use App\Domain\AssetLocations\AssetLocationFilterService;
use App\Models\AssetLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-locations');

test('apply search filters by name', function () {
    AssetLocation::factory()->create(['name' => 'Warehouse A']);
    AssetLocation::factory()->create(['name' => 'Office B']);

    $service = new AssetLocationFilterService();
    $query = AssetLocation::query();
    
    $service->applySearch($query, 'Warehouse', ['name', 'code']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('Warehouse A');
});

test('apply search filters by code', function () {
    AssetLocation::factory()->create(['code' => 'WH-001']);
    AssetLocation::factory()->create(['code' => 'OFF-001']);

    $service = new AssetLocationFilterService();
    $query = AssetLocation::query();
    
    $service->applySearch($query, 'WH', ['name', 'code']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->code)->toBe('WH-001');
});

test('apply advanced filters by branch_id', function () {
    $location1 = AssetLocation::factory()->create();
    $location2 = AssetLocation::factory()->create();

    $service = new AssetLocationFilterService();
    $query = AssetLocation::query();
    
    $service->applyAdvancedFilters($query, ['branch_id' => $location1->branch_id]);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->id)->toBe($location1->id);
});

test('apply advanced filters by parent_id', function () {
    $parent = AssetLocation::factory()->create();
    AssetLocation::factory()->create(['parent_id' => $parent->id, 'branch_id' => $parent->branch_id]);
    AssetLocation::factory()->create();

    $service = new AssetLocationFilterService();
    $query = AssetLocation::query();
    
    $service->applyAdvancedFilters($query, ['parent_id' => $parent->id]);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->parent_id)->toBe($parent->id);
});
