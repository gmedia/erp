<?php

use App\Http\Requests\Pipelines\IndexPipelineRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('pipelines');

test('pipeline request authorizes access', function () {
    $request = new IndexPipelineRequest;

    expect($request->authorize())->toBeTrue();
});

test('pipeline request normalizes boolean and keeps listing rules valid', function () {
    $request = IndexPipelineRequest::create('/api/pipelines', 'GET', [
        'search' => 'Asset',
        'entity_type' => 'App\\Models\\Asset',
        'is_active' => 'true',
        'sort_by' => 'created_by',
        'sort_direction' => 'desc',
        'per_page' => 20,
        'page' => 2,
    ]);

    (fn () => $this->prepareForValidation())->call($request);

    $validator = Validator::make($request->all(), $request->rules());

    expect($request->boolean('is_active'))->toBeTrue()
        ->and($validator->passes())->toBeTrue();
});

test('pipeline request rejects invalid sort direction', function () {
    $request = new IndexPipelineRequest;
    $validator = Validator::make([
        'sort_direction' => 'sideways',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('sort_direction'))->toBeTrue();
});
