<?php

use App\Http\Requests\AssetCategories\ExportAssetCategoryRequest;

uses()->group('asset-categories', 'unit', 'requests');

test('export asset category request rules are present', function () {
    $request = new ExportAssetCategoryRequest();
    $rules = $request->rules();
    
    expect($rules)->toBeArray()
        ->and($rules)->toHaveKey('search');
});
