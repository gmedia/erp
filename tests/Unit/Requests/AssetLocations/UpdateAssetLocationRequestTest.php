<?php

use App\Http\Requests\AssetLocations\UpdateAssetLocationRequest;

uses()->group('asset-locations', 'requests');

test('authorize returns true', function () {
    $request = new UpdateAssetLocationRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new UpdateAssetLocationRequest();

    expect($request->rules())->toEqual([
        'branch_id' => 'sometimes|required|exists:branches,id',
        'parent_id' => 'sometimes|nullable|exists:asset_locations,id',
        'code' => 'sometimes|required|string|max:50',
        'name' => 'sometimes|required|string|max:255',
    ]);
});
