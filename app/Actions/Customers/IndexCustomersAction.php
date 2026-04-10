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
        $query = Customer::query()->with(['branch', 'category']);

        return $this->handleIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['name', 'email', 'phone'],
            ['branch_id', 'category_id', 'status'],
            'created_at',
            ['id', 'name', 'email', 'phone', 'branch_id', 'category_id', 'status', 'created_at', 'updated_at'],
        );
    }
}
