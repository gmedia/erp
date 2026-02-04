<?php

use App\Http\Requests\AssetCategories\IndexAssetCategoryRequest;

uses()->group('asset-categories', 'unit', 'requests');

test('index asset category request rules allow correct sort fields', function () {
    $request = new IndexAssetCategoryRequest();
    $rules = $request->rules();
    
    expect($rules['sort_by'])->toContain('in:id,code,name,useful_life_months_default,created_at,updated_at');
});
