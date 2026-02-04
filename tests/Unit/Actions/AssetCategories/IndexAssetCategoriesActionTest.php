<?php

use App\Actions\AssetCategories\IndexAssetCategoriesAction;
use App\Http\Requests\AssetCategories\IndexAssetCategoryRequest;
use App\Models\AssetCategory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-categories', 'unit', 'actions');

test('index asset categories action execute returns paginated results', function () {
    AssetCategory::factory()->count(3)->create();

    $action = new IndexAssetCategoriesAction();
    $request = new IndexAssetCategoryRequest();
    
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->count())->toBe(3);
});

test('index asset categories action execute filters by search term', function () {
    AssetCategory::factory()->create(['name' => 'KND-001', 'code' => 'KND']);
    AssetCategory::factory()->create(['name' => 'IT-001', 'code' => 'IT']);

    $action = new IndexAssetCategoriesAction();
    $request = new IndexAssetCategoryRequest(['search' => 'KND']);
    
    $result = $action->execute($request);

    expect($result->count())->toBe(1)
        ->and($result->first()->code)->toBe('KND');
});

test('index asset categories action execute sorts by name', function () {
    AssetCategory::factory()->create(['name' => 'B Category', 'code' => 'B']);
    AssetCategory::factory()->create(['name' => 'A Category', 'code' => 'A']);

    $action = new IndexAssetCategoriesAction();
    $request = new IndexAssetCategoryRequest([
        'sort_by' => 'name',
        'sort_direction' => 'asc'
    ]);
    
    $result = $action->execute($request);

    expect($result->first()->name)->toBe('A Category');
});
