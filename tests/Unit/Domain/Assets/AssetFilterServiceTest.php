<?php

namespace Tests\Unit\Domain\Assets;

use App\Domain\Assets\AssetFilterService;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets');

test('asset filter service applies advanced filters', function () {
    $category = AssetCategory::factory()->create();
    $branch = Branch::factory()->create();

    Asset::factory()->create([
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'status' => 'active',
    ]);
    Asset::factory()->create(['status' => 'maintenance']);

    $service = new AssetFilterService;
    $query = Asset::query();

    $service->applyAdvancedFilters($query, [
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'status' => 'active',
    ]);

    expect($query->count())->toBe(1);
});

test('asset filter service applies search', function () {
    Asset::factory()->create(['name' => 'Searchable Asset']);
    Asset::factory()->create(['name' => 'Other Asset']);

    $service = new AssetFilterService;
    $query = Asset::query();

    $service->applySearch($query, 'Searchable', ['name']);

    expect($query->count())->toBe(1);
    expect($query->first()->name)->toBe('Searchable Asset');
});

test('asset filter service sorts by supplier name using normalized direction', function () {
    $supplierA = Supplier::factory()->create(['name' => 'Alpha Supplier']);
    $supplierZ = Supplier::factory()->create(['name' => 'Zulu Supplier']);

    $assetA = Asset::factory()->create(['supplier_id' => $supplierA->id]);
    $assetZ = Asset::factory()->create(['supplier_id' => $supplierZ->id]);

    $service = new AssetFilterService;
    $query = Asset::query();

    $service->applySorting($query, 'supplier', 'ASC', ['name', 'supplier']);

    expect($query->get()->pluck('id')->all())->toBe([$assetA->id, $assetZ->id]);
});
