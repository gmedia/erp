<?php

namespace App\Actions\ApprovalDelegations;

use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use App\Models\ApprovalDelegation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexApprovalDelegationsAction
{
    public function __construct(
        private readonly ApprovalDelegationFilterService $filterService
    ) {}

    public function execute(array $filters): LengthAwarePaginator
    {
        $query = ApprovalDelegation::query()
            ->with(['delegator:id,name', 'delegate:id,name']);

        // Base search across relationships and text fields
        if (! empty($filters['search'])) {
            $this->filterService->applySearch(
                $query,
                $filters['search'],
                ['reason'],
                [
                    'delegator' => ['name'],
                    'delegate' => ['name'],
                ]
            );
        }

        // Apply advanced filters (date_range, is_active, delegator, delegate)
        $this->filterService->applyAdvancedFilters($query, $filters);

        // Apply sorting
        $this->filterService->applySorting(
            $query,
            $filters['sort_by'] ?? 'created_at',
            $filters['sort_direction'] ?? 'desc',
            ['id', 'delegator_user_id', 'delegate_user_id', 'approvable_type', 'start_date', 'end_date', 'is_active', 'created_at', 'updated_at']
        );

        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
