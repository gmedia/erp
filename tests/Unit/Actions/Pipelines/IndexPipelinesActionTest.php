<?php

namespace Tests\Unit\Actions\Pipelines;

use App\Actions\Pipelines\IndexPipelinesAction;
use App\Domain\Pipelines\PipelineFilterService;
use App\Http\Requests\Pipelines\IndexPipelineRequest;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('pipelines');

it('indexes pipelines with pagination', function () {
    Pipeline::factory()->count(15)->create();

    $request = IndexPipelineRequest::create('/api/pipelines', 'GET');
    $action = new IndexPipelinesAction(app(PipelineFilterService::class));
    $result = $action->execute($request);

    expect($result->total())->toBe(15);
    expect($result->perPage())->toBe(15);
});

it('filters pipelines by entity_type', function () {
    Pipeline::factory()->create(['entity_type' => 'App\\Models\\Asset']);
    Pipeline::factory()->create(['entity_type' => 'App\\Models\\PurchaseOrder']);

    $request = IndexPipelineRequest::create('/api/pipelines', 'GET', ['entity_type' => 'App\Models\Asset']);
    $action = new IndexPipelinesAction(app(PipelineFilterService::class));
    $result = $action->execute($request);

    expect($result->total())->toBe(1);
    expect($result->first()->entity_type)->toBe('App\Models\Asset');
});

it('filters pipelines by search', function () {
    Pipeline::factory()->create(['name' => 'Testing Name 123']);
    Pipeline::factory()->create(['name' => 'Other Name']);

    $request = IndexPipelineRequest::create('/api/pipelines', 'GET', ['search' => 'Testing Name']);
    $action = new IndexPipelinesAction(app(PipelineFilterService::class));
    $result = $action->execute($request);

    expect($result->total())->toBe(1);
    expect($result->first()->name)->toBe('Testing Name 123');
});

it('sorts pipelines by created_by', function () {
    $user1 = \App\Models\User::factory()->create(['name' => 'Alice User']);
    $user2 = \App\Models\User::factory()->create(['name' => 'Zack User']);

    Pipeline::factory()->create(['created_by' => $user2->id, 'name' => 'Pipeline Z']);
    Pipeline::factory()->create(['created_by' => $user1->id, 'name' => 'Pipeline A']);

    $request = IndexPipelineRequest::create('/api/pipelines', 'GET', ['sort_by' => 'created_by', 'sort_direction' => 'asc']);
    $action = new IndexPipelinesAction(app(PipelineFilterService::class));
    $result = $action->execute($request);

    expect($result->total())->toBe(2);
    expect($result->first()->name)->toBe('Pipeline A');
});
