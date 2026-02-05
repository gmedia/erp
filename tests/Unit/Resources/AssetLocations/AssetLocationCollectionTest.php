<?php

use App\Http\Resources\AssetLocations\AssetLocationCollection;
use App\Models\AssetLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-locations', 'resources');

test('to array transforms collection', function () {
    $assetLocations = AssetLocation::factory()->count(3)->create();
    
    $collection = new AssetLocationCollection($assetLocations);
    $result = $collection->response()->getData(true);
    
    expect($result)->toHaveKey('data')
        ->and($result['data'])->toHaveCount(3);
});

test('collection includes all items with correct structure', function () {
    AssetLocation::factory()->create(['code' => 'LOC-001', 'name' => 'Location 1']);
    AssetLocation::factory()->create(['code' => 'LOC-002', 'name' => 'Location 2']);
    
    $assetLocations = AssetLocation::all();
    $collection = new AssetLocationCollection($assetLocations);
    $result = $collection->response()->getData(true);
    
    expect($result)->toHaveKey('data')
        ->and($result['data'])->toHaveCount(2);
    
    $firstItem = $result['data'][0];
    expect($firstItem)->toHaveKeys(['id', 'code', 'name', 'branch', 'parent']);
});
