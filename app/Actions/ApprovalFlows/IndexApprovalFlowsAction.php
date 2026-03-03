<?php

namespace App\Actions\ApprovalFlows;

use App\Domain\ApprovalFlows\ApprovalFlowFilterService;
use App\Http\Requests\ApprovalFlows\IndexApprovalFlowRequest;
use App\Models\ApprovalFlow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexApprovalFlowsAction
{
    public function __construct(
        private ApprovalFlowFilterService $filterService
    ) {}

    public function execute(IndexApprovalFlowRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = ApprovalFlow::query()->with(['steps.user', 'steps.department', 'creator']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'code']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'approvable_type' => $request->get('approvable_type'),
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'is_active' => $request->get('is_active'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'code', 'approvable_type', 'is_active', 'created_at', 'updated_at']
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
