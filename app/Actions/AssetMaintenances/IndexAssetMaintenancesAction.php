<?php

namespace App\Actions\AssetMaintenances;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\AssetMaintenances\AssetMaintenanceFilterService;
use App\Http\Requests\AssetMaintenances\IndexAssetMaintenanceRequest;
use App\Models\AssetMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetMaintenancesAction
{
    use InteractsWithIndexRequest;

    public function __construct(
        private AssetMaintenanceFilterService $filterService
    ) {}

    public function execute(IndexAssetMaintenanceRequest $request): LengthAwarePaginator
    {
        ['perPage' => $perPage, 'page' => $page] = $this->getPaginationParams($request);

        $query = AssetMaintenance::query()->with(['asset', 'supplier', 'createdBy']);

        $this->applySearchOrPrimaryFilters(
            $request,
            $query,
            $this->filterService,
            ['notes', 'asset_name', 'asset_code'],
            ['asset_id', 'maintenance_type', 'status', 'supplier_id', 'created_by'],
        );

        $this->applyRequestFilters(
            $request,
            $query,
            $this->filterService,
            ['scheduled_from', 'scheduled_to', 'performed_from', 'performed_to', 'cost_min', 'cost_max'],
        );

        $this->applyIndexSorting(
            $request,
            $query,
            $this->filterService,
            'scheduled_at',
            [
                'id',
                'asset',
                'maintenance_type',
                'status',
                'scheduled_at',
                'performed_at',
                'supplier',
                'notes',
                'cost',
                'created_at',
                'updated_at',
            ],
        );

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
