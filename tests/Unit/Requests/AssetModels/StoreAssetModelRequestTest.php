<?php

use App\Http\Requests\AssetModels\StoreAssetModelRequest;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-models');

test('store request has required rules', function () {
    $request = new StoreAssetModelRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKey('model_name')
        ->and($rules)->toHaveKey('asset_category_id')
        ->and($rules['model_name'])->toContain('required')
        ->and($rules['asset_category_id'])->toContain('required');
});

test('store request allows nullable manufacturer', function () {
    $request = new StoreAssetModelRequest();
    $rules = $request->rules();

    expect($rules['manufacturer'])->toContain('nullable');
});

test('store request allows nullable specs', function () {
    $request = new StoreAssetModelRequest();
    $rules = $request->rules();

    expect($rules['specs'])->toContain('nullable');
});
