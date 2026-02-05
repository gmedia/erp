<?php

use App\Http\Requests\AssetModels\ExportAssetModelRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-models');

test('export request allowed fields', function () {
    $request = new ExportAssetModelRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'search',
        'asset_category_id',
        'sort_by',
        'sort_direction',
    ]);
});

test('export request parameters are nullable', function () {
    $request = new ExportAssetModelRequest();
    $rules = $request->rules();

    expect($rules['search'])->toContain('nullable')
        ->and($rules['asset_category_id'])->toContain('nullable')
        ->and($rules['sort_by'])->toContain('nullable')
        ->and($rules['sort_direction'])->toContain('nullable');
});
