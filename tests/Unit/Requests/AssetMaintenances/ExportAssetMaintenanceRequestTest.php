<?php

namespace Tests\Unit\Requests\AssetMaintenances;

use App\Http\Requests\AssetMaintenances\ExportAssetMaintenanceRequest;

uses()->group('asset-maintenances');

function createExportRequest(): ExportAssetMaintenanceRequest
{
    return new ExportAssetMaintenanceRequest();
}

test('it authorizes request', function () {
    expect(createExportRequest()->authorize())->toBeTrue();
});

test('it validates valid data', function () {
    $data = [
        'search' => 'test',
        'maintenance_type' => 'preventive',
        'status' => 'scheduled',
        'sort_by' => 'scheduled_at',
        'sort_direction' => 'desc',
    ];

    $validator = validator($data, createExportRequest()->rules());
    expect($validator->fails())->toBeFalse();
});

test('it validates sort direction', function () {
    foreach (['asc', 'desc'] as $dir) {
        $validator = validator(['sort_direction' => $dir], createExportRequest()->rules());
        expect($validator->fails())->toBeFalse();
    }

    $validator = validator(['sort_direction' => 'invalid'], createExportRequest()->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('sort_direction'))->toBeTrue();
});
