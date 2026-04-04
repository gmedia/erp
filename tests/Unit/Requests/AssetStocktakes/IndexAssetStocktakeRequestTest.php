<?php

use App\Http\Requests\AssetStocktakes\IndexAssetStocktakeRequest;
use App\Models\Branch;
use Illuminate\Support\Facades\Validator;

uses()->group('asset-stocktakes');

test('asset stocktake request authorizes access', function () {
    $request = new IndexAssetStocktakeRequest;

    expect($request->authorize())->toBeTrue();
});

test('asset stocktake request validates listing filters', function () {
    $branch = Branch::factory()->create();
    $request = new IndexAssetStocktakeRequest;

    $validator = Validator::make([
        'search' => 'STK-001',
        'branch_id' => $branch->id,
        'status' => 'completed',
        'sort_by' => 'planned_at',
        'sort_direction' => 'asc',
        'per_page' => 25,
        'page' => 1,
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('asset stocktake request rejects invalid status', function () {
    $request = new IndexAssetStocktakeRequest;
    $validator = Validator::make([
        'status' => 'unknown',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('status'))->toBeTrue();
});
