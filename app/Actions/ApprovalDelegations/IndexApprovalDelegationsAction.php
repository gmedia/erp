<?php

namespace App\Actions\ApprovalDelegations;

use App\Actions\ApprovalDelegations\Concerns\InteractsWithApprovalDelegationQuery;
use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Http\FormRequest;

class IndexApprovalDelegationsAction
{
    use InteractsWithApprovalDelegationQuery;
    use InteractsWithIndexRequest;

    public function __construct(
        private readonly ApprovalDelegationFilterService $filterService
    ) {}

    public function execute(FormRequest $request): LengthAwarePaginator
    {
        $query = $this->buildFilteredQuery(
            $this->filterService,
            $request->validated(),
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

        return $this->handlePreparedIndexRequest($request, $query, 10);
    }
}
