<?php

use App\Http\Requests\AssetLocations\ExportAssetLocationRequest;

uses()->group('asset-locations');

test('authorize returns true', function () {
    $request = new ExportAssetLocationRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportAssetLocationRequest();

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'branch_id' => ['nullable', 'exists:branches,id'],
        'parent_id' => ['nullable', 'exists:asset_locations,id'],
        'sort_by' => ['nullable', 'string', 'in:code,name,branch,parent,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
    ]);
});
