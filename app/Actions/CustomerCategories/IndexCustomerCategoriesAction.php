<?php

namespace App\Actions\CustomerCategories;

use App\Domain\CustomerCategories\CustomerCategoryFilterService;
use App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest;
use App\Models\CustomerCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve paginated customer categories with filtering and sorting.
 */
class IndexCustomerCategoriesAction
{
    public function __construct(
        private CustomerCategoryFilterService $filterService
    ) {}

    /**
     * Execute the action to retrieve paginated customer categories with filters.
     *
     * @param  \App\Http\Requests\CustomerCategories\IndexCustomerCategoryRequest  $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<\App\Models\CustomerCategory>
     */
    public function execute(IndexCustomerCategoryRequest $request): LengthAwarePaginator
    {
        $query = CustomerCategory::query();

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
