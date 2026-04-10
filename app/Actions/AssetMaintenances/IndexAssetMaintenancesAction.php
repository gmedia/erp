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
        $query = AssetMaintenance::query()->with(['asset', 'supplier', 'createdBy']);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['notes', 'asset_name', 'asset_code'],
            ['asset_id', 'maintenance_type', 'status', 'supplier_id', 'created_by'],
            ['scheduled_from', 'scheduled_to', 'performed_from', 'performed_to', 'cost_min', 'cost_max'],
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
    }
}
