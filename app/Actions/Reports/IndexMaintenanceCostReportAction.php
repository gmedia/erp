<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Actions\Reports\Concerns\SumsReportTotals;
use App\Http\Requests\Reports\IndexMaintenanceCostRequest;
use App\Models\AssetMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexMaintenanceCostReportAction
{
    use HandlesReportQuery;
    use SumsReportTotals;

    public function execute(
        IndexMaintenanceCostRequest $request
    ): LengthAwarePaginator|Collection {
        $query = $this->buildFilteredQuery($request);
        $this->applyMaintenanceCostReportSorting($request, $query);

        return $this->exportOrPaginate($request, $query);
    }

    /**
     * Total untuk SELURUH hasil filter (bukan hanya halaman aktif).
     *
     * @return array{as_of_date: string, totals: array<string, string>}
     */
    public function summarize(IndexMaintenanceCostRequest $request): array
    {
        return $this->buildReportSummary(
            $this->buildFilteredQuery($request)->reorder(),
            ['cost'],
        );
    }

    private function buildFilteredQuery(IndexMaintenanceCostRequest $request): Builder
    {
        $query = AssetMaintenance::query()
            ->with(['asset.category', 'asset.branch', 'supplier']);

        $this->applyMaintenanceCostReportFilters($request, $query);

        return $query;
    }
}
