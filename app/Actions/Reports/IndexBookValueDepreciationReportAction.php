<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Http\Requests\Reports\IndexBookValueDepreciationRequest;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class IndexBookValueDepreciationReportAction
{
    use HandlesReportQuery;

    public function execute(IndexBookValueDepreciationRequest $request): LengthAwarePaginator|Collection
    {
        $query = Asset::query()
            ->with(['category', 'branch'])
            ->whereIn('status', ['active', 'maintenance', 'disposed', 'lost']); // Typically draft are not depreciated

        $this->applySearchFilter($request, $query, ['asset_code', 'name']);
        $this->applyIntegerFilters($request, $query, [
            'asset_category_id' => 'asset_category_id',
            'branch_id' => 'branch_id',
        ]);
        $this->applyRequestSorting(
            $request,
            $query,
            'asset_code',
            [],
            ['asset_code', 'name', 'purchase_date', 'purchase_cost', 'book_value', 'accumulated_depreciation'],
            [],
            'asset_code',
            'asc',
        );

        return $this->exportOrPaginate($request, $query);
    }
}
