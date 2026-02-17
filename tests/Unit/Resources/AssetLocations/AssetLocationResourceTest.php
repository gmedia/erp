<?php

use App\Http\Resources\AssetLocations\AssetLocationResource;
use App\Models\AssetLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('asset-locations');

test('to array returns correct structure', function () {
    $assetLocation = AssetLocation::factory()->create([
        'code' => 'WH-001',
        'name' => 'Main Warehouse',
    ]);
    
    $resource = new AssetLocationResource($assetLocation);
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result)->toMatchArray([
        'id' => $assetLocation->id,
        'code' => 'WH-001',
        'name' => 'Main Warehouse',
    ]);
    
    expect($result)->toHaveKeys(['branch', 'parent', 'created_at', 'updated_at']);
});

test('to array includes branch relation', function () {
    $assetLocation = AssetLocation::factory()->create();
    
    $resource = new AssetLocationResource($assetLocation->load('branch'));
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result['branch'])->toHaveKeys(['id', 'name'])
        ->and($result['branch']['id'])->toBe($assetLocation->branch_id);
});

test('to array includes parent relation when present', function () {
    $parent = AssetLocation::factory()->create(['name' => 'Parent Location']);
    $child = AssetLocation::factory()->create([
        'parent_id' => $parent->id,
        'branch_id' => $parent->branch_id,
    ]);
    
    $resource = new AssetLocationResource($child->load('parent'));
    $request = Request::create('/');
    
    $result = $resource->toArray($request);
    
    expect($result['parent'])->toHaveKeys(['id', 'name'])
        ->and($result['parent']['id'])->toBe($parent->id)
        ->and($result['parent']['name'])->toBe('Parent Location');
});
