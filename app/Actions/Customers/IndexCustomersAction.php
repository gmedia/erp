<?php

namespace App\Actions\Customers;

use App\Domain\Customers\CustomerFilterService;
use App\Http\Requests\Customers\IndexCustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexCustomersAction
{
    public function __construct(
        private CustomerFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated customers with filters.
     */
    public function execute(IndexCustomerRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Customer::query()->with(['branch']);

        // Search functionality - search across name, email, phone
        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'email', 'phone']);
        }

        // Apply advanced filters
        $this->filterService->applyAdvancedFilters($query, [
            'branch_id' => $request->get('branch'),
            'customer_type' => $request->get('customer_type'),
            'status' => $request->get('status'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'email', 'phone', 'branch_id', 'customer_type', 'status', 'created_at', 'updated_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get pagination parameters from request
     */
    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
