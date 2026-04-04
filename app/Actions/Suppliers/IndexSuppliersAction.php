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
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Supplier::query()->with(['branch', 'category']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'email', 'phone', 'address']);
        }

        $this->applyRequestFilters($request, $query, $this->filterService, ['branch_id', 'category_id', 'status']);
        $this->applyIndexSorting(
            $request,
            $query,
            $this->filterService,
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
            ]
        );

        return $this->paginateIndexQuery($query, $perPage, $page);
    }
}
