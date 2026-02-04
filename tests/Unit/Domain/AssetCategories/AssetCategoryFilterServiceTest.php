<?php

use App\Domain\AssetCategories\AssetCategoryFilterService;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-categories', 'unit', 'domain');

test('asset category filter service applies name search', function () {
    AssetCategory::factory()->create(['name' => 'IT Equipment']);
    AssetCategory::factory()->create(['name' => 'Office Chair']);

    $service = new AssetCategoryFilterService();
    $query = AssetCategory::query();
    
    $service->applySearch($query, 'IT', ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe('IT Equipment');
});

test('asset category filter service applies code search', function () {
    AssetCategory::factory()->create(['code' => 'AC-001']);
    AssetCategory::factory()->create(['code' => 'FE-001']);

    $service = new AssetCategoryFilterService();
    $query = AssetCategory::query();
    
    $service->applySearch($query, 'AC', ['code']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->code)->toBe('AC-001');
});
