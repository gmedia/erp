<?php

namespace Tests\Unit\Requests\AssetMaintenances;

use App\Http\Requests\AssetMaintenances\IndexAssetMaintenanceRequest;

uses()->group('asset-maintenances');

function createIndexRequest(): IndexAssetMaintenanceRequest
{
    return new IndexAssetMaintenanceRequest();
}

test('it authorizes request', function () {
    expect(createIndexRequest()->authorize())->toBeTrue();
});

test('it validates valid data', function () {
    $data = [
        'search' => 'test',
        'maintenance_type' => 'preventive',
        'status' => 'scheduled',
        'scheduled_from' => '2023-01-01',
        'scheduled_to' => '2023-12-31',
        'cost_min' => 0,
        'cost_max' => 100000,
        'sort_by' => 'scheduled_at',
        'sort_direction' => 'desc',
        'per_page' => 15,
        'page' => 1,
    ];

    $validator = validator($data, createIndexRequest()->rules());
    expect($validator->fails())->toBeFalse();
});

test('it validates sort by columns', function () {
    $validColumns = ['id', 'asset', 'maintenance_type', 'status', 'scheduled_at', 'performed_at', 'supplier', 'notes', 'cost', 'created_at', 'updated_at'];

    foreach ($validColumns as $column) {
        $validator = validator(['sort_by' => $column], createIndexRequest()->rules());
        expect($validator->fails())->toBeFalse();
    }

    $validator = validator(['sort_by' => 'invalid_col'], createIndexRequest()->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('sort_by'))->toBeTrue();
});

test('it validates maintenance type enum', function () {
    foreach (['preventive', 'corrective', 'calibration', 'other'] as $type) {
        $validator = validator(['maintenance_type' => $type], createIndexRequest()->rules());
        expect($validator->fails())->toBeFalse();
    }

    $validator = validator(['maintenance_type' => 'invalid_type'], createIndexRequest()->rules());
    expect($validator->fails())->toBeTrue();
});

test('it validates status enum', function () {
    foreach (['scheduled', 'in_progress', 'completed', 'cancelled'] as $status) {
        $validator = validator(['status' => $status], createIndexRequest()->rules());
        expect($validator->fails())->toBeFalse();
    }

    $validator = validator(['status' => 'invalid_status'], createIndexRequest()->rules());
    expect($validator->fails())->toBeTrue();
});
