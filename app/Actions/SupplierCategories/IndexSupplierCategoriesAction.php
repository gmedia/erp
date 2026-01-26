<?php

namespace App\Actions\SupplierCategories;

use App\Domain\SupplierCategories\SupplierCategoryFilterService;
use App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve paginated supplier categories with filtering and sorting.
 */
class IndexSupplierCategoriesAction
{
    public function __construct(
        private SupplierCategoryFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated supplier categories with filters.
     *
     * @param  \App\Http\Requests\SupplierCategories\IndexSupplierCategoryRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\SupplierCategory>
     */
    public function execute(IndexSupplierCategoryRequest $request): LengthAwarePaginator
    {
        $query = SupplierCategory::query();

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name']);
        }

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'name', 'created_at', 'updated_at']
        );

        return $query->paginate($request->get('per_page', 15));
    }
}
