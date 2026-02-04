<?php

use App\Http\Resources\AssetCategories\AssetCategoryResource;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-categories', 'unit', 'resources');

test('asset category resource returns correct data', function () {
    $category = AssetCategory::factory()->create([
        'code' => 'TEST-RES',
        'name' => 'Resource Test',
        'useful_life_months_default' => 48,
    ]);

    $resource = new AssetCategoryResource($category);
    $data = $resource->toArray(request());

    expect($data)->toBeArray()
        ->and($data['id'])->toBe($category->id)
        ->and($data['code'])->toBe('TEST-RES')
        ->and($data['name'])->toBe('Resource Test')
        ->and($data['useful_life_months_default'])->toBe(48)
        ->and($data['created_at'])->toBe($category->created_at->toIso8601String())
        ->and($data['updated_at'])->toBe($category->updated_at->toIso8601String());
});
