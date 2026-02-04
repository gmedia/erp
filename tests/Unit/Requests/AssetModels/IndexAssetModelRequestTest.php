<?php

use App\Http\Requests\AssetModels\IndexAssetModelRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-models');

test('index request allows search parameter', function () {
    $request = new IndexAssetModelRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKey('search')
        ->and($rules['search'])->toContain('nullable');
});

test('index request allows asset_category_id filter', function () {
    $request = new IndexAssetModelRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKey('asset_category_id');
});

test('index request allows sorting parameters', function () {
    $request = new IndexAssetModelRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKey('sort_by')
        ->and($rules)->toHaveKey('sort_direction');
});

test('index request allows pagination parameters', function () {
    $request = new IndexAssetModelRequest();
    $rules = $request->rules();

    expect($rules)->toHaveKey('per_page')
        ->and($rules)->toHaveKey('page');
});
