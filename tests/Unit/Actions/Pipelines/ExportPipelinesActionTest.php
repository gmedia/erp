<?php

namespace Tests\Unit\Actions\Pipelines;

use App\Actions\Pipelines\ExportPipelinesAction;
use App\Domain\Pipelines\PipelineFilterService;
use App\Exports\PipelineExport;
use App\Http\Requests\Pipelines\ExportPipelineRequest;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;

uses(RefreshDatabase::class)->group('pipelines');

it('exports pipelines', function () {
    Pipeline::factory()->count(5)->create();

    $request = Mockery::mock(ExportPipelineRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    $action = new ExportPipelinesAction(app(PipelineFilterService::class));
    $result = $action->execute($request);

    expect($result->getStatusCode())->toBe(200);
    $data = json_decode($result->getContent(), true);
    expect($data)->toHaveKeys(['url', 'filename']);
});

it('preserves false boolean filters when exporting pipelines', function () {
    Storage::fake('public');

    $request = Mockery::mock(ExportPipelineRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'is_active' => false,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    Excel::shouldReceive('store')
        ->once()
        ->withArgs(function (object $export, string $filePath, string $disk): bool {
            if (! $export instanceof PipelineExport || $disk !== 'public') {
                return false;
            }

            $filters = (function (): array {
                return $this->filters;
            })->call($export);

            return $filePath !== ''
                && array_key_exists('is_active', $filters)
                && $filters['is_active'] === false;
        });

    $action = new ExportPipelinesAction(app(PipelineFilterService::class));
    $result = $action->execute($request);

    expect($result->getStatusCode())->toBe(200);
});
