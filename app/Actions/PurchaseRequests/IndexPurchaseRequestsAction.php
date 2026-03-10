<?php

namespace App\Actions\PurchaseRequests;

use App\Domain\PurchaseRequests\PurchaseRequestFilterService;
use App\Http\Requests\PurchaseRequests\IndexPurchaseRequestRequest;
use App\Models\PurchaseRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexPurchaseRequestsAction
{
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

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->string('search')->toString(), [
                'pr_number',
                'notes',
                'rejection_reason',
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'branch_id' => $request->get('branch_id'),
            'department_id' => $request->get('department_id'),
            'requested_by' => $request->get('requested_by'),
            'priority' => $request->get('priority'),
            'status' => $request->get('status'),
            'request_date_from' => $request->get('request_date_from'),
            'request_date_to' => $request->get('request_date_to'),
            'required_date_from' => $request->get('required_date_from'),
            'required_date_to' => $request->get('required_date_to'),
            'estimated_amount_min' => $request->get('estimated_amount_min'),
            'estimated_amount_max' => $request->get('estimated_amount_max'),
        ]);

        $sortBy = $request->string('sort_by', 'created_at')->toString();
        $sortDirection = strtolower($request->string('sort_direction', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'branch' => 'branch_id',
            'department' => 'department_id',
            'requester' => 'requested_by',
        ];

        $sortBy = $sortMap[$sortBy] ?? $sortBy;

        $this->filterService->applySorting($query, $sortBy, $sortDirection, [
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
        ]);

        return $query->paginate(
            $request->integer('per_page', 15),
            ['*'],
            'page',
            $request->integer('page', 1),
        );
    }
}
