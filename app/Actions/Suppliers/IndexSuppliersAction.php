<?php

namespace App\Actions\Suppliers;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\Suppliers\SupplierFilterService;
use App\Http\Requests\Suppliers\IndexSupplierRequest;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexSuppliersAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private SupplierFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated suppliers with filters.
     */
    public function execute(IndexSupplierRequest $request): LengthAwarePaginator
    {
        $query = Supplier::query()->with(['branch', 'category']);

        return $this->handleIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['name', 'email', 'phone', 'address'],
            ['branch_id', 'category_id', 'status'],
            'created_at',
            [
                'id',
                'name',
                'email',
                'phone',
                'address',
                'branch_id',
                'category_id',
                'status',
                'created_at',
                'updated_at',
            ],
        );
    }
}
