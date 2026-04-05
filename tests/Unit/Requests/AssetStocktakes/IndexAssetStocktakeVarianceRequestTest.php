<?php

use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeVarianceRequest;

uses()->group('asset-stocktakes');

test('authorize returns true for asset stocktake variance index request', function () {
    $request = new IndexAssetStocktakeVarianceRequest;

    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules for asset stocktake variance index request', function () {
    $request = new IndexAssetStocktakeVarianceRequest;

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'asset_stocktake_id' => ['nullable', 'exists:asset_stocktakes,id'],
        'branch_id' => ['nullable', 'exists:branches,id'],
        'result' => ['nullable', 'in:missing,damaged,moved'],
        'sort_by' => [
            'nullable',
            'string',
            'in:id,stocktake_reference,asset_code,asset_name,expected_branch,' .
                'expected_location,found_branch,found_location,result,checked_at',
        ],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
