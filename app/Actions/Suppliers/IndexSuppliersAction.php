<?php

namespace App\Actions\Suppliers;

use App\Domain\Suppliers\SupplierFilterService;
use App\Http\Requests\Suppliers\IndexSupplierRequest;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexSuppliersAction
{
    public function __construct(
        private SupplierFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated suppliers with filters.
     */
    public function execute(IndexSupplierRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Supplier::query()->with(['branch']);

        // Search functionality - search across name, email, phone, address
        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'email', 'phone', 'address']);
        }

        // Apply advanced filters
        $this->filterService->applyAdvancedFilters($query, [
            'branch_id' => $request->get('branch'),
            'category' => $request->get('category'),
            'status' => $request->get('status'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'email', 'phone', 'address', 'branch_id', 'category', 'status', 'created_at', 'updated_at']
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
