<?php

namespace Tests\Unit\Requests\AssetMovements;

use App\Http\Requests\AssetMovements\ExportAssetMovementRequest;
use Tests\TestCase;

uses()->group('asset-movements');

function createExportRequest(): ExportAssetMovementRequest
{
    return new ExportAssetMovementRequest();
}

test('it authorizes request', function () {
    $request = createExportRequest();
    expect($request->authorize())->toBeTrue();
});

test('it validates valid data', function () {
    $data = [
        'search' => 'test search',
        'movement_type' => 'transfer',
        'sort_by' => 'moved_at',
        'sort_direction' => 'desc',
    ];

    $validator = validator($data, createExportRequest()->rules());
    expect($validator->fails())->toBeFalse();
});

test('it validates sort by columns', function () {
    // Valid
    $validColumns = ['moved_at', 'movement_type', 'created_at'];
    foreach ($validColumns as $column) {
        $validator = validator(['sort_by' => $column], createExportRequest()->rules());
        expect($validator->fails())->toBeFalse("Sort by $column should pass");
    }

    // Invalid
    $validator = validator(['sort_by' => 'invalid_col'], createExportRequest()->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('sort_by'))->toBeTrue();
});

test('it validates sort direction', function () {
    // Valid
    foreach (['asc', 'desc'] as $dir) {
        $validator = validator(['sort_direction' => $dir], createExportRequest()->rules());
        expect($validator->fails())->toBeFalse();
    }

    // Invalid
    $validator = validator(['sort_direction' => 'invalid'], createExportRequest()->rules());
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('sort_direction'))->toBeTrue();
});

test('it validates movement type enum', function () {
    // Valid
    $types = ['acquired', 'transfer', 'assign', 'return', 'dispose', 'adjustment'];
    foreach ($types as $type) {
        $validator = validator(['movement_type' => $type], createExportRequest()->rules());
        expect($validator->fails())->toBeFalse();
    }

    // Invalid
    $validator = validator(['movement_type' => 'invalid_type'], createExportRequest()->rules());
    expect($validator->fails())->toBeTrue();
});
