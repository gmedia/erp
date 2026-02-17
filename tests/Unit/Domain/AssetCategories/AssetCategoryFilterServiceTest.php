<?php

use App\Domain\AssetCategories\AssetCategoryFilterService;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-categories');

test('asset category filter service applies name search', function () {
    $needle = 'ACAT-' . uniqid();
    AssetCategory::factory()->create(['name' => "{$needle} Equipment"]);
    AssetCategory::factory()->create(['name' => 'Office Chair']);

    $service = new AssetCategoryFilterService();
    $query = AssetCategory::query();
    
    $service->applySearch($query, $needle, ['name']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->name)->toBe("{$needle} Equipment");
});

test('asset category filter service applies code search', function () {
    $needle = 'CODE-' . uniqid();
    AssetCategory::factory()->create(['code' => "AC-{$needle}"]);
    AssetCategory::factory()->create(['code' => "FE-{$needle}"]);

    $service = new AssetCategoryFilterService();
    $query = AssetCategory::query();
    
    $service->applySearch($query, "AC-{$needle}", ['code']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->code)->toBe("AC-{$needle}");
});
