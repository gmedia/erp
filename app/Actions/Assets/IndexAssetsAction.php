<?php

namespace App\Actions\Assets;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\Assets\AssetFilterService;
use App\Http\Requests\Assets\IndexAssetRequest;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetsAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AssetFilterService $filterService
    ) {}

    public function execute(IndexAssetRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Asset::query()->with([
            'category',
            'model',
            'branch',
            'location',
            'department',
            'employee',
            'supplier',
        ]);

        $this->applySearchOrPrimaryFilters(
            $request,
            $query,
            $this->filterService,
            ['name', 'asset_code', 'serial_number', 'barcode'],
            ['asset_category_id', 'asset_model_id', 'branch_id', 'asset_location_id', 'department_id', 'employee_id', 'supplier_id', 'status', 'condition'],
        );

        $this->applyIndexSorting(
            $request,
            $query,
            $this->filterService,
            'created_at',
            [
                'id',
                'asset_code',
                'name',
                'purchase_date',
                'purchase_cost',
                'status',
                'created_at',
                'category',
                'branch',
                'location',
                'department',
                'employee',
                'supplier',
            ],
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
