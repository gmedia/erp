<?php

use App\Http\Requests\AssetLocations\IndexAssetLocationRequest;

uses()->group('asset-locations', 'requests');

test('authorize returns true', function () {
    $request = new IndexAssetLocationRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexAssetLocationRequest();

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'branch_id' => ['nullable', 'exists:branches,id'],
        'parent_id' => ['nullable', 'exists:asset_locations,id'],
        'sort_by' => ['nullable', 'string', 'in:id,code,name,branch_id,parent_id,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
