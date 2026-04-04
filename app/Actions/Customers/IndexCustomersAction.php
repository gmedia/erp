<?php

namespace App\Actions\Customers;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\Customers\CustomerFilterService;
use App\Http\Requests\Customers\IndexCustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexCustomersAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private CustomerFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated customers with filters.
     */
    public function execute(IndexCustomerRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Customer::query()->with(['branch', 'category']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'email', 'phone']);
        }

        $this->applyRequestFilters($request, $query, $this->filterService, ['branch_id', 'category_id', 'status']);
        $this->applyIndexSorting(
            $request,
            $query,
            $this->filterService,
            'created_at',
            ['id', 'name', 'email', 'phone', 'branch_id', 'category_id', 'status', 'created_at', 'updated_at']
        );

        return $this->paginateIndexQuery($query, $perPage, $page);
    }
}
