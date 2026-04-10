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
        $query = Product::query()->with(['category', 'unit', 'branch']);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['code', 'name', 'description'],
            ['category_id', 'unit_id', 'branch_id'],
            ['type', 'status', 'billing_model', 'is_manufactured', 'is_purchasable', 'is_sellable'],
            'created_at',
            [
                'id',
                'code',
                'name',
                'type',
                'category',
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
}
