<?php

use App\Http\Requests\AssetStocktakes\UpdateAssetStocktakeItemRequest;

uses()->group('asset-stocktakes');

test('update asset stocktake item request authorizes access', function () {
    $request = new UpdateAssetStocktakeItemRequest;

    expect($request->authorize())->toBeTrue();
});

test('update asset stocktake item request contains expected item rules', function () {
    $request = new UpdateAssetStocktakeItemRequest;
    $rules = $request->rules();

    expect($rules)->toHaveKeys([
        'items',
        'items.*.asset_id',
        'items.*.expected_branch_id',
        'items.*.result',
    ]);

    expect($rules['items'])->toContain('required', 'array')
        ->and($rules['items.*.asset_id'])->toContain('required', 'exists:assets,id')
        ->and($rules['items.*.expected_branch_id'])->toContain('required', 'exists:branches,id')
        ->and($rules['items.*.result'])->toContain('required', 'in:found,missing,damaged,moved');
});
