<?php

use App\Http\Requests\AssetModels\UpdateAssetModelRequest;
use App\Models\AssetModel;
use App\Models\AssetCategory;
use Illuminate\Support\Facades\Validator;

uses()->group('asset-models');

test('update request validation rules', function () {
    $assetModel = AssetModel::factory()->create();
    $request = new UpdateAssetModelRequest();
    
    // Set route parameter for unique validation if applicable
    $request->setRouteResolver(function () use ($assetModel) {
        return (object) ['parameter' => fn() => $assetModel->id];
    });

    $rules = $request->rules();

    expect($rules['asset_category_id'])->toContain('sometimes', 'required', 'exists:asset_categories,id')
        ->and($rules['manufacturer'])->toContain('sometimes', 'nullable', 'string', 'max:255')
        ->and($rules['model_name'])->toContain('sometimes', 'required', 'string', 'max:255')
        ->and($rules['specs'])->toContain('sometimes', 'nullable', 'array');
});

test('update request validation passes with valid data', function () {
    $assetModel = AssetModel::factory()->create();
    $category = AssetCategory::factory()->create();
    
    $data = [
        'asset_category_id' => $category->id,
        'model_name' => 'Updated Model Name',
    ];

    $request = new UpdateAssetModelRequest();
    $rules = $request->rules();
    
    $validator = Validator::make($data, $rules);
    
    expect($validator->passes())->toBeTrue();
});
