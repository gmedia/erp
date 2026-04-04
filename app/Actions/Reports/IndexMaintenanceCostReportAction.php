<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexMaintenanceCostRequest;
use App\Models\AssetMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexMaintenanceCostReportAction
{
    use HandlesReportQuery;

    public function execute(
        IndexMaintenanceCostRequest $request
    ): LengthAwarePaginator|Collection {
        $query = AssetMaintenance::query()
            ->with(['asset.category', 'asset.branch', 'supplier']);

        $this->applyMaintenanceCostReportFilters($request, $query);
        $this->applyMaintenanceCostReportSorting($request, $query);

        return $this->exportOrPaginate($request, $query);
    }
}
