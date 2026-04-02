<?php

namespace App\Actions\ApprovalDelegations;

use App\Actions\ApprovalDelegations\Concerns\InteractsWithApprovalDelegationQuery;
use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexApprovalDelegationsAction
{
    use InteractsWithApprovalDelegationQuery;

    public function __construct(
        private readonly ApprovalDelegationFilterService $filterService
    ) {}

    public function execute(array $filters): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery(
            $this->filterService,
            $filters,
            [
                'id',
                'delegator_user_id',
                'delegate_user_id',
                'approvable_type',
                'start_date',
                'end_date',
                'is_active',
                'created_at',
                'updated_at',
            ],
        );

        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
