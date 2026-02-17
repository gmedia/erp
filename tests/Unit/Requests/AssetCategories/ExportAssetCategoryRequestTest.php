<?php

use App\Http\Requests\AssetCategories\ExportAssetCategoryRequest;

uses()->group('asset-categories');

test('export asset category request rules are present', function () {
    $request = new ExportAssetCategoryRequest();
    $rules = $request->rules();
    
    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('search');
});
