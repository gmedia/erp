<?php

namespace Tests\Unit\Requests\AssetMaintenances;

use App\Http\Requests\AssetMaintenances\UpdateAssetMaintenanceRequest;

uses()->group('asset-maintenances');

function createUpdateRequest(): UpdateAssetMaintenanceRequest
{
    return new UpdateAssetMaintenanceRequest();
}

test('it authorizes request', function () {
    expect(createUpdateRequest()->authorize())->toBeTrue();
});

test('it allows partial updates', function () {
    $validator = validator(['notes' => 'Updated'], createUpdateRequest()->rules());
    expect($validator->fails())->toBeFalse();
});

test('it validates enums when provided', function () {
    $validator = validator([
        'maintenance_type' => 'invalid',
        'status' => 'invalid',
    ], createUpdateRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('maintenance_type'))->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});
