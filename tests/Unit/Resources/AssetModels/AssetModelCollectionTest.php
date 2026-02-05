<?php

use App\Http\Resources\AssetModels\AssetModelCollection;
use App\Http\Resources\AssetModels\AssetModelResource;
use App\Models\AssetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('asset-models');

test('collection returns correct structure', function () {
    $assetModels = AssetModel::factory()->count(3)->create();
    $assetModels->load('category');

    $collection = new AssetModelCollection($assetModels);
    $response = $collection->toArray(new Request());

    expect($response['data'])->toHaveCount(3)
        ->and($response['data'][0])->toBeInstanceOf(AssetModelResource::class);
});
