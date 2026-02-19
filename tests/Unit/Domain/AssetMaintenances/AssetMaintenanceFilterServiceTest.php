<?php

namespace Tests\Unit\Domain\AssetMaintenances;

use App\Domain\AssetMaintenances\AssetMaintenanceFilterService;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-maintenances');

test('it can filter by search on notes', function () {
    AssetMaintenance::factory()->create(['notes' => 'FIND-ME']);
    AssetMaintenance::factory()->create(['notes' => 'OTHER']);

    $service = new AssetMaintenanceFilterService();
    $query = AssetMaintenance::query();

    $service->applySearch($query, 'FIND-ME', ['notes']);

    expect($query->count())->toBe(1)
        ->and($query->first()->notes)->toBe('FIND-ME');
});

test('it can filter by search on related asset_code', function () {
    $asset = Asset::factory()->create(['asset_code' => 'FA-SEARCH']);
    AssetMaintenance::factory()->create(['asset_id' => $asset->id]);
    AssetMaintenance::factory()->create();

    $service = new AssetMaintenanceFilterService();
    $query = AssetMaintenance::query();

    $service->applySearch($query, 'FA-SEARCH', ['asset_code']);

    expect($query->count())->toBe(1)
        ->and($query->first()->asset_id)->toBe($asset->id);
});

test('it can filter by status', function () {
    AssetMaintenance::factory()->create(['status' => 'scheduled']);
    AssetMaintenance::factory()->create(['status' => 'completed']);

    $service = new AssetMaintenanceFilterService();
    $query = AssetMaintenance::query();

    $service->applyAdvancedFilters($query, ['status' => 'completed']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('completed');
});

test('it can sort by cost', function () {
    $m1 = AssetMaintenance::factory()->create(['cost' => 100]);
    $m2 = AssetMaintenance::factory()->create(['cost' => 200]);

    $service = new AssetMaintenanceFilterService();
    $query = AssetMaintenance::query();

    $service->applySorting($query, 'cost', 'desc', ['cost']);

    $results = $query->get();

    expect($results->first()->id)->toBe($m2->id)
        ->and($results->last()->id)->toBe($m1->id);
});
