<?php

use App\Http\Resources\AssetCategories\AssetCategoryCollection;
use App\Http\Resources\AssetCategories\AssetCategoryResource;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-categories', 'unit', 'resources');

test('asset category collection uses asset category resource', function () {
    AssetCategory::factory()->count(3)->create();
    
    $collection = new AssetCategoryCollection(AssetCategory::all());
    
    expect($collection->collects)->toBe(AssetCategoryResource::class);
});
