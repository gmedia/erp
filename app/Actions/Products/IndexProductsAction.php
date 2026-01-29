<?php

namespace App\Actions\Products;

use App\Domain\Products\ProductFilterService;
use App\Http\Requests\Products\IndexProductRequest;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexProductsAction
{
    public function __construct(
        private ProductFilterService $filterService
    ) {}

    public function execute(IndexProductRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Product::query()->with(['category', 'unit', 'branch']);

        // Search OR Advanced Filters (category, unit, branch)
        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['code', 'name', 'description']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'category_id' => $request->get('category_id'),
                'unit_id' => $request->get('unit_id'),
                'branch_id' => $request->get('branch_id'),
            ]);
        }

        // Apply remaining advanced filters
        $this->filterService->applyAdvancedFilters($query, [
            'type' => $request->get('type'),
            'status' => $request->get('status'),
            'billing_model' => $request->get('billing_model'),
            'is_manufactured' => $request->get('is_manufactured'),
            'is_purchasable' => $request->get('is_purchasable'),
            'is_sellable' => $request->get('is_sellable'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'code', 'name', 'type', 'category_id', 'unit_id', 'cost', 'selling_price', 'status', 'created_at', 'updated_at']
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    private function getPaginationParams($request): array
    {
        return [
            'perPage' => $request->get('per_page', 15),
            'page' => $request->get('page', 1),
        ];
    }
}
