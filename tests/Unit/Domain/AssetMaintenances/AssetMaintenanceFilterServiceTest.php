<?php

namespace Tests\Unit\Domain\AssetMaintenances;

use App\Domain\AssetMaintenances\AssetMaintenanceFilterService;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-maintenances');

test('it can filter by search on notes', function () {
    AssetMaintenance::factory()->create(['notes' => 'FIND-ME']);
    AssetMaintenance::factory()->create(['notes' => 'OTHER']);

    $service = new AssetMaintenanceFilterService;
    $query = AssetMaintenance::query();

    $service->applySearch($query, 'FIND-ME', ['notes']);

    expect($query->count())->toBe(1)
        ->and($query->first()->notes)->toBe('FIND-ME');
});

test('it can filter by search on related asset_code', function () {
    $asset = Asset::factory()->create(['asset_code' => 'FA-SEARCH']);
    AssetMaintenance::factory()->create(['asset_id' => $asset->id]);
    AssetMaintenance::factory()->create();

    $service = new AssetMaintenanceFilterService;
    $query = AssetMaintenance::query();

    $service->applySearch($query, 'FA-SEARCH', ['asset_code']);

    expect($query->count())->toBe(1)
        ->and($query->first()->asset_id)->toBe($asset->id);
});

test('it can filter by status', function () {
    AssetMaintenance::factory()->create(['status' => 'scheduled']);
    AssetMaintenance::factory()->create(['status' => 'completed']);

    $service = new AssetMaintenanceFilterService;
    $query = AssetMaintenance::query();

    $service->applyAdvancedFilters($query, ['status' => 'completed']);

    expect($query->count())->toBe(1)
        ->and($query->first()->status)->toBe('completed');
});

test('it can sort by cost', function () {
    $m1 = AssetMaintenance::factory()->create(['cost' => 100]);
    $m2 = AssetMaintenance::factory()->create(['cost' => 200]);

    $service = new AssetMaintenanceFilterService;
    $query = AssetMaintenance::query();

    $service->applySorting($query, 'cost', 'desc', ['cost']);

    $results = $query->get();

    expect($results->first()->id)->toBe($m2->id)
        ->and($results->last()->id)->toBe($m1->id);
});

test('it can sort by related asset code', function () {
    $assetA = Asset::factory()->create(['asset_code' => 'AST-001']);
    $assetZ = Asset::factory()->create(['asset_code' => 'AST-999']);

    $maintenanceZ = AssetMaintenance::factory()->create(['asset_id' => $assetZ->id]);
    $maintenanceA = AssetMaintenance::factory()->create(['asset_id' => $assetA->id]);

    $service = new AssetMaintenanceFilterService;
    $query = AssetMaintenance::query();

    $service->applySorting($query, 'asset', 'asc', ['asset', 'cost']);

    expect($query->get()->pluck('id')->all())->toBe([$maintenanceA->id, $maintenanceZ->id]);
});

test('it can sort by related supplier name using normalized direction', function () {
    $supplierA = Supplier::factory()->create(['name' => 'Alpha Supplier']);
    $supplierZ = Supplier::factory()->create(['name' => 'Zulu Supplier']);

    $maintenanceA = AssetMaintenance::factory()->create(['supplier_id' => $supplierA->id]);
    $maintenanceZ = AssetMaintenance::factory()->create(['supplier_id' => $supplierZ->id]);

    $service = new AssetMaintenanceFilterService;
    $query = AssetMaintenance::query();

    $service->applySorting($query, 'supplier', 'ASC', ['supplier', 'cost']);

    expect($query->get()->pluck('id')->all())->toBe([$maintenanceA->id, $maintenanceZ->id]);
});
