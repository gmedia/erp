<?php

namespace App\Actions\PurchaseRequests;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\PurchaseRequests\PurchaseRequestFilterService;
use App\Http\Requests\PurchaseRequests\IndexPurchaseRequestRequest;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPurchaseRequestsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private PurchaseRequestFilterService $filterService
    ) {}

    public function execute(IndexPurchaseRequestRequest $request): LengthAwarePaginator
    {
        $query = PurchaseRequest::query()->with([
            'branch',
            'department',
            'requester',
            'approver',
            'creator',
            'items.product',
            'items.unit',
        ]);

        return $this->handleMappedIndexRequest($request, $query, $this->filterService, [
            'pr_number',
            'notes',
            'rejection_reason',
        ], [
            'branch_id',
            'department_id',
            'requested_by',
            'priority',
            'status',
            'request_date_from',
            'request_date_to',
            'required_date_from',
            'required_date_to',
            'estimated_amount_min',
            'estimated_amount_max',
        ], 'created_at', [
            'id',
            'pr_number',
            'branch_id',
            'department_id',
            'requested_by',
            'request_date',
            'required_date',
            'priority',
            'status',
            'estimated_amount',
            'created_at',
            'updated_at',
        ], [
            'branch' => 'branch_id',
            'department' => 'department_id',
            'requester' => 'requested_by',
        ]);
    }
}
