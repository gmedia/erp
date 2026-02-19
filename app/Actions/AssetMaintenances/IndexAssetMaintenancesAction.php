<?php

namespace App\Actions\AssetMaintenances;

use App\Domain\AssetMaintenances\AssetMaintenanceFilterService;
use App\Http\Requests\AssetMaintenances\IndexAssetMaintenanceRequest;
use App\Models\AssetMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetMaintenancesAction
{
    public function __construct(
        private AssetMaintenanceFilterService $filterService
    ) {}

    public function execute(IndexAssetMaintenanceRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = AssetMaintenance::query()->with(['asset', 'supplier', 'createdBy']);

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $request->get('search'), ['notes', 'asset_name', 'asset_code']);
        } else {
            $this->filterService->applyAdvancedFilters($query, [
                'asset_id' => $request->get('asset_id'),
                'maintenance_type' => $request->get('maintenance_type'),
                'status' => $request->get('status'),
                'supplier_id' => $request->get('supplier_id'),
                'created_by' => $request->get('created_by'),
            ]);
        }

        $this->filterService->applyAdvancedFilters($query, [
            'scheduled_from' => $request->get('scheduled_from'),
            'scheduled_to' => $request->get('scheduled_to'),
            'performed_from' => $request->get('performed_from'),
            'performed_to' => $request->get('performed_to'),
            'cost_min' => $request->get('cost_min'),
            'cost_max' => $request->get('cost_max'),
        ]);

        $this->filterService->applySorting(
            $query,
            $request->get('sort_by', 'scheduled_at'),
            $request->get('sort_direction', 'desc'),
            ['id', 'asset', 'maintenance_type', 'status', 'scheduled_at', 'performed_at', 'supplier', 'notes', 'cost', 'created_at', 'updated_at']
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
