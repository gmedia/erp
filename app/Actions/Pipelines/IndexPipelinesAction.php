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

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sortBy === 'created_by') {
            $query->leftJoin('users as creator', 'pipelines.created_by', '=', 'creator.id')
                  ->orderBy('creator.name', $sortDirection)
                  ->select('pipelines.*');
        } else {
            $this->filterService->applySorting(
                $query,
                $sortBy,
                $sortDirection,
                ['id', 'name', 'code', 'entity_type', 'version', 'is_active', 'created_at', 'updated_at']
            );
        }

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
