<?php

use App\Http\Requests\AssetCategories\StoreAssetCategoryRequest;

uses()->group('asset-categories', 'unit', 'requests');

test('store asset category request authorize returns true', function () {
    $request = new StoreAssetCategoryRequest();
    expect($request->authorize())->toBeTrue();
});

test('store asset category request rules are correct', function () {
    $request = new StoreAssetCategoryRequest();
    
    expect($request->rules())->toEqual([
        'code' => ['required', 'string', 'max:255', 'unique:asset_categories,code'],
        'name' => ['required', 'string', 'max:255'],
        'useful_life_months_default' => ['nullable', 'integer', 'min:0'],
    ]);
});
