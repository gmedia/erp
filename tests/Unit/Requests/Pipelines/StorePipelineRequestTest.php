<?php

use App\Http\Requests\Pipelines\StorePipelineRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('pipelines');

test('store pipeline request authorizes access', function () {
    $request = new StorePipelineRequest;

    expect($request->authorize())->toBeTrue();
});

test('store pipeline request validates required fields', function () {
    $request = new StorePipelineRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain('name', 'code', 'entity_type');
});

test('store pipeline request accepts valid payload', function () {
    $request = new StorePipelineRequest;
    $validator = Validator::make([
        'name' => 'Asset Pipeline',
        'code' => 'asset_pipeline',
        'entity_type' => 'App\\Models\\Asset',
        'description' => 'Flow for asset approval',
        'version' => 1,
        'is_active' => true,
        'conditions' => json_encode(['status' => 'draft']),
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});
