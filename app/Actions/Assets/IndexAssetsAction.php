<?php

namespace App\Actions\Assets;

use App\Domain\Assets\AssetFilterService;
use App\Http\Requests\Assets\IndexAssetRequest;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetsAction
{
    public function __construct(
        private AssetFilterService $filterService
    ) {}

    public function execute(IndexAssetRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = Asset::query()->with(['category', 'model', 'branch', 'location', 'department', 'employee']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['name', 'asset_code', 'serial_number', 'barcode']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'asset_category_id' => $request->get('asset_category_id'),
                'asset_model_id' => $request->get('asset_model_id'),
                'branch_id' => $request->get('branch_id'),
                'asset_location_id' => $request->get('asset_location_id'),
                'department_id' => $request->get('department_id'),
                'employee_id' => $request->get('employee_id'),
                'status' => $request->get('status'),
                'condition' => $request->get('condition'),
            ]);
        }

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'created_at'),
            strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc',
            ['id', 'asset_code', 'name', 'purchase_date', 'purchase_cost', 'status', 'created_at', 'category', 'branch']
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
