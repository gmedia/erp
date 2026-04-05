<?php

use App\Http\Requests\Pipelines\ExportPipelineRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('pipelines');

test('pipeline export request authorizes access', function () {
    $request = new ExportPipelineRequest;

    expect($request->authorize())->toBeTrue();
});

test('pipeline export request normalizes boolean and keeps export rules valid', function () {
    $request = ExportPipelineRequest::create('/api/pipelines/export', 'GET', [
        'search' => 'Asset',
        'entity_type' => 'App\\Models\\Asset',
        'is_active' => 'false',
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    (fn () => $this->prepareForValidation())->call($request);

    $validator = Validator::make($request->all(), $request->rules());

    expect($request->boolean('is_active'))->toBeFalse()
        ->and($validator->passes())->toBeTrue();
});

test('pipeline export request rejects invalid sort direction', function () {
    $request = new ExportPipelineRequest;
    $validator = Validator::make([
        'sort_direction' => 'sideways',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('sort_direction'))->toBeTrue();
});
