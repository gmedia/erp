<?php

namespace App\Actions\Pipelines;

use App\Domain\Pipelines\PipelineFilterService;
use App\Http\Requests\Pipelines\IndexPipelineRequest;
use App\Models\Pipeline;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPipelinesAction
{
    public function __construct(
        private PipelineFilterService $filterService
    ) {}

    public function execute(IndexPipelineRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Pipeline::query()->with(['creator']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'code', 'description']);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'entity_type' => $request->get('entity_type'),
            'is_active' => $request->get('is_active'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'code', 'entity_type', 'version', 'is_active', 'created_at', 'updated_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
