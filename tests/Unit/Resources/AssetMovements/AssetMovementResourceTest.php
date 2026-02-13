<?php

namespace Tests\Unit\Resources\AssetMovements;

use App\Http\Resources\AssetMovements\AssetMovementResource;
use App\Models\Asset;
use App\Models\AssetMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

uses(RefreshDatabase::class)->group('asset-movements');

test('it transforms the resource correctly', function () {
    $asset = Asset::factory()->create();
    $assetMovement = AssetMovement::factory()->create([
        'asset_id' => $asset->id,
        'reference' => 'REF-TEST',
        'notes' => 'Test Notes',
    ]);

    $resource = new AssetMovementResource($assetMovement);
    $data = $resource->resolve();

    expect($data)->toBeArray();
    expect($data)->toHaveKey('id', $assetMovement->id);
    expect($data)->toHaveKey('asset_id', $asset->id);
    expect($data)->toHaveKey('reference', 'REF-TEST');
    expect($data)->toHaveKey('notes', 'Test Notes');
    expect($data)->toHaveKey('movement_type', $assetMovement->movement_type);
    expect($data)->toHaveKey('moved_at'); // Might need formatting check
    
    // Check relationships if loaded
    $assetMovement->load('asset');
    $resourceLoaded = new AssetMovementResource($assetMovement);
    $dataLoaded = $resourceLoaded->resolve();
    
    expect($dataLoaded)->toHaveKey('asset');
    expect($dataLoaded['asset'])->toHaveKey('id', $asset->id);
});
