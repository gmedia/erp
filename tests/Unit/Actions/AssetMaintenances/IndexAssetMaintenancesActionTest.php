<?php

namespace Tests\Unit\Actions\AssetMaintenances;

use App\Actions\AssetMaintenances\IndexAssetMaintenancesAction;
use App\Http\Requests\AssetMaintenances\IndexAssetMaintenanceRequest;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class)->group('asset-maintenances');

test('it can index with filters', function () {
    AssetMaintenance::factory()->create(['status' => 'scheduled']);
    $completed = AssetMaintenance::factory()->create(['status' => 'completed']);

    $request = Mockery::mock(IndexAssetMaintenanceRequest::class);

    $request->shouldReceive('filled')->with('search')->andReturn(false);

    $request->shouldReceive('get')->andReturnUsing(function ($key, $default = null) {
        $map = [
            'asset_id' => null,
            'maintenance_type' => null,
            'status' => 'completed',
            'supplier_id' => null,
            'created_by' => null,
            'scheduled_from' => null,
            'scheduled_to' => null,
            'performed_from' => null,
            'performed_to' => null,
            'cost_min' => null,
            'cost_max' => null,
            'sort_by' => 'id',
            'sort_direction' => 'asc',
            'per_page' => 15,
            'page' => 1,
        ];

        return $map[$key] ?? $default;
    });

    $action = app(IndexAssetMaintenancesAction::class);
    $result = $action->execute($request);

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->id)->toBe($completed->id);
});
