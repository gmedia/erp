<?php

use App\Http\Requests\AssetLocations\StoreAssetLocationRequest;

uses()->group('asset-locations', 'requests');

test('authorize returns true', function () {
    $request = new StoreAssetLocationRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreAssetLocationRequest();
    
    expect($request->rules())->toEqual([
        'branch_id' => 'required|exists:branches,id',
        'parent_id' => 'nullable|exists:asset_locations,id',
        'code' => 'required|string|max:50',
        'name' => 'required|string|max:255',
    ]);
});
