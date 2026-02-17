<?php

namespace Tests\Unit\Domain\Assets;

use App\Domain\Assets\AssetFilterService;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('assets');

test('asset filter service applies advanced filters', function () {
    $category = AssetCategory::factory()->create();
    $branch = Branch::factory()->create();
    
    Asset::factory()->create([
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'status' => 'active'
    ]);
    Asset::factory()->create(['status' => 'maintenance']);

    $service = new AssetFilterService();
    $query = Asset::query();
    
    $service->applyAdvancedFilters($query, [
        'asset_category_id' => $category->id,
        'branch_id' => $branch->id,
        'status' => 'active'
    ]);

    expect($query->count())->toBe(1);
});

test('asset filter service applies search', function () {
    Asset::factory()->create(['name' => 'Searchable Asset']);
    Asset::factory()->create(['name' => 'Other Asset']);

    $service = new AssetFilterService();
    $query = Asset::query();
    
    $service->applySearch($query, 'Searchable', ['name']);

    expect($query->count())->toBe(1);
    expect($query->first()->name)->toBe('Searchable Asset');
});
