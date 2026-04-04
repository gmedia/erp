<?php

namespace App\Actions\ApprovalFlows;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\ApprovalFlows\ApprovalFlowFilterService;
use App\Http\Requests\ApprovalFlows\IndexApprovalFlowRequest;
use App\Models\ApprovalFlow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexApprovalFlowsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private ApprovalFlowFilterService $filterService
    ) {}

    public function execute(IndexApprovalFlowRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = ApprovalFlow::query()->with(['steps.user', 'steps.department', 'creator']);

        $this->applySearchOrPrimaryFilters($request, $query, $this->filterService, ['name', 'code'], ['approvable_type']);
        $this->applyRequestFilters($request, $query, $this->filterService, ['is_active']);
        $this->applyIndexSorting(
            $request,
            $query,
            $this->filterService,
            'created_at',
            ['id', 'name', 'code', 'approvable_type', 'is_active', 'created_at', 'updated_at']
        );

        return $this->paginateIndexQuery($query, $perPage, $page);
    }
}
