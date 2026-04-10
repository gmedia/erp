<?php

namespace App\Actions\Pipelines;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\Pipelines\PipelineFilterService;
use App\Http\Requests\Pipelines\IndexPipelineRequest;
use App\Models\Pipeline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPipelinesAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private PipelineFilterService $filterService
    ) {}

    public function execute(IndexPipelineRequest $request): LengthAwarePaginator
    {
        $query = Pipeline::query()->with(['creator']);

        return $this->handleIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['name', 'code', 'description'],
            ['entity_type', 'is_active'],
            'created_at',
            ['id', 'name', 'code', 'entity_type', 'version', 'is_active', 'created_by', 'created_at', 'updated_at'],
        );
    }
}
