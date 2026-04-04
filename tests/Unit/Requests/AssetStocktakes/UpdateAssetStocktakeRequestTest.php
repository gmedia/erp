<?php

use App\Http\Requests\AssetStocktakes\UpdateAssetStocktakeRequest;
use App\Models\AssetStocktake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('asset-stocktakes');

test('update asset stocktake request authorizes access', function () {
    $request = new UpdateAssetStocktakeRequest;

    expect($request->authorize())->toBeTrue();
});

test('update asset stocktake request allows partial valid payload', function () {
    $stocktake = AssetStocktake::factory()->create();
    $request = new UpdateAssetStocktakeRequest;
    $request->setRouteResolver(function () use ($stocktake) {
        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('parameter')->with('asset_stocktake', Mockery::any())->andReturn($stocktake);

        return $mockRoute;
    });

    $validator = Validator::make([
        'reference' => 'AST-UPDATED',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('update asset stocktake request rejects duplicate reference from another stocktake in same branch', function () {
    $existing = AssetStocktake::factory()->create(['reference' => 'AST-EXISTING']);
    $stocktake = AssetStocktake::factory()->create(['branch_id' => $existing->branch_id, 'reference' => 'AST-OLD']);
    $request = new UpdateAssetStocktakeRequest;
    $request->setRouteResolver(function () use ($stocktake) {
        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('parameter')->with('asset_stocktake', Mockery::any())->andReturn($stocktake);

        return $mockRoute;
    });

    $validator = Validator::make([
        'reference' => $existing->reference,
        'status' => 'draft',
        'planned_at' => '2024-01-01',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('reference'))->toBeTrue();
});
