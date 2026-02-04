<?php

use App\Http\Resources\AssetModels\AssetModelResource;
use App\Models\AssetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('asset-models');

test('resource returns correct structure', function () {
    $assetModel = AssetModel::factory()->create();
    $assetModel->load('category');

    $resource = new AssetModelResource($assetModel);
    $response = $resource->toArray(new Request());

    expect($response)->toHaveKeys([
        'id',
        'model_name',
        'manufacturer',
        'specs',
        'category',
        'created_at',
        'updated_at',
    ]);
});

test('resource returns nested category object', function () {
    $assetModel = AssetModel::factory()->create();
    $assetModel->load('category');

    $resource = new AssetModelResource($assetModel);
    $response = $resource->toArray(new Request());

    expect($response['category'])->toBeArray()
        ->and($response['category'])->toHaveKeys(['id', 'name']);
});
