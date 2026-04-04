<?php

use App\Http\Requests\AssetStocktakes\StoreAssetStocktakeRequest;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('store asset stocktake request authorizes access', function () {
    $request = new StoreAssetStocktakeRequest;

    expect($request->authorize())->toBeTrue();
});

test('store asset stocktake request validates required fields', function () {
    $request = new StoreAssetStocktakeRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('branch_id', 'reference', 'planned_at', 'status');
});

test('store asset stocktake request accepts valid payload', function () {
    $branch = Branch::factory()->create();
    $request = new StoreAssetStocktakeRequest;

    $validator = Validator::make([
        'branch_id' => $branch->id,
        'reference' => 'AST-001',
        'planned_at' => '2024-01-01',
        'status' => 'draft',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});
