<?php

namespace Tests\Unit\Actions\Pipelines;

use App\Actions\Pipelines\ExportPipelinesAction;
use App\Domain\Pipelines\PipelineFilterService;
use App\Http\Requests\Pipelines\ExportPipelineRequest;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('pipelines');

it('exports pipelines', function () {
    Pipeline::factory()->count(5)->create();

    $request = \Mockery::mock(ExportPipelineRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    $action = new ExportPipelinesAction(app(PipelineFilterService::class));
    $result = $action->execute($request);

    expect($result->getStatusCode())->toBe(200);
    $data = json_decode($result->getContent(), true);
    expect($data)->toHaveKeys(['url', 'filename']);
});
