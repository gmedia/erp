<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\InteractsWithStockSnapshotQuery;
use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexInventoryValuationReportRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexInventoryValuationReportAction
{
    use HandlesReportQuery;
    use InteractsWithStockSnapshotQuery;

    public function execute(IndexInventoryValuationReportRequest $request): LengthAwarePaginator|Collection
    {
        $stockValueExpr = $this->stockSnapshotValueExpression();
        $query = $this->buildStockSnapshotQuery(includeProductUnit: true);

        $this->applyStockSnapshotFilters($request, $query);
        $this->applyStockSnapshotSorting($request, $query, $stockValueExpr, 'stock_value');

        return $this->exportOrPaginate($request, $query);
    }
}
