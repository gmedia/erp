<?php

use App\Models\AssetCategory;
use App\Models\AssetModel;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-models');

test('asset model has correct fillable fields', function () {
    $model = new AssetModel();
    
    expect($model->getFillable())->toBe([
        'asset_category_id',
        'manufacturer',
        'model_name',
        'specs',
    ]);
});

test('asset model belongs to an asset category', function () {
    $category = AssetCategory::factory()->create();
    $model = AssetModel::factory()->create(['asset_category_id' => $category->id]);
    
    expect($model->category)->toBeInstanceOf(AssetCategory::class)
        ->and($model->category->id)->toBe($category->id);
});

test('asset model has many assets', function () {
    $model = AssetModel::factory()->create();
    $asset = Asset::factory()->create(['asset_model_id' => $model->id]);
    
    expect($model->assets)->toHaveCount(1)
        ->and($model->assets->first())->toBeInstanceOf(Asset::class)
        ->and($model->assets->first()->id)->toBe($asset->id);
});

test('asset model casts specs to array', function () {
    $model = AssetModel::factory()->create([
        'specs' => ['color' => 'red', 'weight' => '1kg']
    ]);
    
    expect($model->specs)->toBeArray()
        ->and($model->specs)->toBe(['color' => 'red', 'weight' => '1kg']);
});
