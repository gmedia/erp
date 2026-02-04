<?php

use App\Models\AssetCategory;
use App\Models\AssetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('asset-models');

test('factory creates a valid asset model', function () {
    $assetModel = AssetModel::factory()->create();

    assertDatabaseHas('asset_models', ['id' => $assetModel->id]);

    expect($assetModel->getAttributes())->toMatchArray([
        'model_name' => $assetModel->model_name,
        'manufacturer' => $assetModel->manufacturer,
        'asset_category_id' => $assetModel->asset_category_id,
    ]);
});

test('asset model belongs to a category', function () {
    $category = AssetCategory::factory()->create();
    $assetModel = AssetModel::factory()->create(['asset_category_id' => $category->id]);

    expect($assetModel->category)->toBeInstanceOf(AssetCategory::class)
        ->and($assetModel->category->id)->toBe($category->id);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new AssetModel)->getFillable();

    expect($fillable)->toBe([
        'asset_category_id',
        'manufacturer',
        'model_name',
        'specs',
    ]);
});

test('specs is cast to array', function () {
    $assetModel = AssetModel::factory()->create([
        'specs' => ['cpu' => 'i7', 'ram_gb' => 16],
    ]);

    expect($assetModel->specs)->toBeArray()
        ->and($assetModel->specs['cpu'])->toBe('i7');
});
