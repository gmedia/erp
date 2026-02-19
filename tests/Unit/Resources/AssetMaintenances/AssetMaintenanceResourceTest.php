<?php

namespace Tests\Unit\Resources\AssetMaintenances;

use App\Http\Resources\AssetMaintenances\AssetMaintenanceResource;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-maintenances');

test('it transforms the resource correctly', function () {
    $asset = Asset::factory()->create(['asset_code' => 'FA-TEST']);

    $maintenance = AssetMaintenance::factory()->create([
        'asset_id' => $asset->id,
        'maintenance_type' => 'preventive',
        'status' => 'scheduled',
        'notes' => 'Test Notes',
    ]);

    $resource = new AssetMaintenanceResource($maintenance->load('asset'));
    $data = $resource->resolve();

    expect($data)->toBeArray();
    expect($data)->toHaveKey('id', $maintenance->id);
    expect($data)->toHaveKey('asset_id', $asset->id);
    expect($data)->toHaveKey('maintenance_type', 'preventive');
    expect($data)->toHaveKey('status', 'scheduled');
    expect($data)->toHaveKey('notes', 'Test Notes');
    expect($data)->toHaveKey('asset');
    expect($data['asset'])->toHaveKey('asset_code', 'FA-TEST');
});
