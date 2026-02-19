<?php

namespace Tests\Unit\Models;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-maintenances');

test('it has fillable attributes', function () {
    $model = new AssetMaintenance();

    expect($model->getFillable())->toBe([
        'asset_id',
        'maintenance_type',
        'status',
        'scheduled_at',
        'performed_at',
        'supplier_id',
        'cost',
        'notes',
        'created_by',
    ]);
});

test('it belongs to an asset', function () {
    $maintenance = AssetMaintenance::factory()->create();
    expect($maintenance->asset)->toBeInstanceOf(Asset::class);
});

test('it belongs to a supplier', function () {
    $maintenance = AssetMaintenance::factory()->create(['supplier_id' => Supplier::factory()]);
    expect($maintenance->supplier)->toBeInstanceOf(Supplier::class);
});

test('it belongs to a creator', function () {
    $maintenance = AssetMaintenance::factory()->create(['created_by' => User::factory()]);
    expect($maintenance->createdBy)->toBeInstanceOf(User::class);
});
