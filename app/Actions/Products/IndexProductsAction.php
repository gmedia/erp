<?php

namespace App\Actions\Products;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\Products\ProductFilterService;
use App\Http\Requests\Products\IndexProductRequest;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexProductsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private ProductFilterService $filterService
    ) {}

    public function execute(IndexProductRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Product::query()->with(['category', 'unit', 'branch']);

        $this->applySearchOrPrimaryFilters(
            $request,
            $query,
            $this->filterService,
            ['code', 'name', 'description'],
            ['category_id', 'unit_id', 'branch_id'],
        );

        $this->applyRequestFilters(
            $request,
            $query,
            $this->filterService,
            ['type', 'status', 'billing_model', 'is_manufactured', 'is_purchasable', 'is_sellable'],
        );

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $this->normalizeSortDirection($request->get('sort_direction', 'desc'));

        if ($sortBy === 'category') {
            $query
                ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
                ->select('products.*')
                ->orderBy('product_categories.name', $sortDirection);
        } else {
            $this->filterService->applySorting(
                $query,
                $sortBy,
                $sortDirection,
                [
                    'id',
                    'code',
                    'name',
                    'type',
                    'category_id',
                    'unit_id',
                    'cost',
                    'selling_price',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            );
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
