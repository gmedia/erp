<?php

use App\Actions\AssetModels\IndexAssetModelsAction;
use App\Http\Requests\AssetModels\IndexAssetModelRequest;
use App\Models\AssetModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class)->group('asset-models');

test('index action returns paginated asset models', function () {
    AssetModel::factory()->count(5)->create();
    
    $action = app(IndexAssetModelsAction::class);
    $request = new IndexAssetModelRequest();
    
    $result = $action->execute($request);
    
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(5);
});

test('index action applies search', function () {
    AssetModel::factory()->create(['model_name' => 'Matching Model']);
    AssetModel::factory()->create(['model_name' => 'Other Model']);
    
    $action = app(IndexAssetModelsAction::class);
    $request = new IndexAssetModelRequest(['search' => 'Matching']);
    
    $result = $action->execute($request);
    
    expect($result->total())->toBe(1)
        ->and($result->getCollection()->first()->model_name)->toBe('Matching Model');
});
