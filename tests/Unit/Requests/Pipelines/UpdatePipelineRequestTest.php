<?php

use App\Http\Requests\Pipelines\UpdatePipelineRequest;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class)->group('pipelines');

test('update pipeline request authorizes access', function () {
    $request = new UpdatePipelineRequest;

    expect($request->authorize())->toBeTrue();
});

test('update pipeline request allows partial valid payload', function () {
    $pipeline = Pipeline::factory()->create();
    $request = new UpdatePipelineRequest;
    $request->setRouteResolver(function () use ($pipeline) {
        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('parameter')->with('pipeline', Mockery::any())->andReturn($pipeline);

        return $mockRoute;
    });

    $validator = Validator::make([
        'name' => 'Renamed Pipeline',
        'is_active' => false,
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('update pipeline request rejects duplicate code from another pipeline', function () {
    $existing = Pipeline::factory()->create(['code' => 'existing_code']);
    $pipeline = Pipeline::factory()->create(['code' => 'old_code']);
    $request = new UpdatePipelineRequest;
    $request->setRouteResolver(function () use ($pipeline) {
        $mockRoute = Mockery::mock(Route::class);
        $mockRoute->shouldReceive('parameter')->with('pipeline', Mockery::any())->andReturn($pipeline);

        return $mockRoute;
    });

    $validator = Validator::make([
        'code' => $existing->code,
        'name' => 'Updated Pipeline',
        'entity_type' => 'App\\Models\\Asset',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('code'))->toBeTrue();
});
