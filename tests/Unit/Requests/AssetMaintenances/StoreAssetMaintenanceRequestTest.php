<?php

namespace Tests\Unit\Requests\AssetMaintenances;

use App\Http\Requests\AssetMaintenances\StoreAssetMaintenanceRequest;

uses()->group('asset-maintenances');

function createStoreRequest(): StoreAssetMaintenanceRequest
{
    return new StoreAssetMaintenanceRequest();
}

test('it authorizes request', function () {
    expect(createStoreRequest()->authorize())->toBeTrue();
});

test('it validates required fields', function () {
    $validator = validator([], createStoreRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('asset_id'))->toBeTrue();
    expect($validator->errors()->has('maintenance_type'))->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

test('it validates enums', function () {
    $validator = validator([
        'asset_id' => 1,
        'maintenance_type' => 'invalid',
        'status' => 'invalid',
    ], createStoreRequest()->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('maintenance_type'))->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});
