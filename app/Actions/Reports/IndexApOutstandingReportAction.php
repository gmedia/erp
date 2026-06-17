<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\BuildsSupplierBillReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexApOutstandingReportAction
{
    use BuildsSupplierBillReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $this->guardSupplierBillCurrency($request, 'AP Outstanding Report');

        // days_overdue computed in PHP via Carbon (cross-DB). See ApOutstandingReportResource.
        $query = $this->buildBaseSupplierBillQuery();

        $this->applySupplierBillFilters($request, $query);
        $this->applyDateRangeFilter($request, $query, 'sb.due_date', 'due_date_from', 'due_date_to');
        $this->applySupplierBillSorting($request, $query, extraSortMap: ['amount_paid' => 'sb.amount_paid']);

        return $this->exportOrPaginate($request, $query);
    }
}
