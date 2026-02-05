<?php

use App\Domain\AssetModels\AssetModelFilterService;
use App\Models\AssetModel;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-models');

test('it can filter by search term', function () {
    $category = AssetCategory::factory()->create();
    AssetModel::factory()->create([
        'model_name' => 'Specific Model Name',
        'manufacturer' => 'Specific Manufacturer',
        'asset_category_id' => $category->id,
    ]);
    AssetModel::factory()->create([
        'model_name' => 'Other Model',
        'manufacturer' => 'Other Company',
        'asset_category_id' => $category->id,
    ]);

    $service = new AssetModelFilterService();
    $query = AssetModel::query();
    
    $service->applySearch($query, 'Specific', ['model_name', 'manufacturer']);
    
    expect($query->count())->toBe(1);
});

test('it can filter by asset category', function () {
    $category1 = AssetCategory::factory()->create();
    $category2 = AssetCategory::factory()->create();
    
    AssetModel::factory()->create(['asset_category_id' => $category1->id]);
    AssetModel::factory()->create(['asset_category_id' => $category2->id]);

    $service = new AssetModelFilterService();
    $query = AssetModel::query();
    
    $service->applyAdvancedFilters($query, ['asset_category_id' => $category1->id]);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->asset_category_id)->toBe($category1->id);
});
