<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\BuildsSupplierBillReportQuery;
use App\Actions\Reports\Concerns\SumsReportTotals;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexApOutstandingReportAction
{
    use BuildsSupplierBillReportQuery;
    use SumsReportTotals;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $this->guardSupplierBillCurrency($request, 'AP Outstanding Report');

        $query = $this->buildFilteredQuery($request);

        $this->applySupplierBillSorting($request, $query, extraSortMap: ['amount_paid' => 'sb.amount_paid']);

        return $this->exportOrPaginate($request, $query);
    }

    /**
     * Total + as-of untuk seluruh hasil filter (bukan satu halaman).
     *
     * @return array{as_of_date: string, totals: array<string, string>}
     */
    public function summarize(FormRequest $request): array
    {
        return $this->buildReportSummary($this->buildFilteredQuery($request), [
            'grand_total',
            'amount_paid',
            'amount_due',
        ]);
    }

    private function buildFilteredQuery(FormRequest $request): Builder
    {
        // days_overdue computed in PHP via Carbon (cross-DB). See ApOutstandingReportResource.
        $query = $this->buildBaseSupplierBillQuery();

        $this->applySupplierBillFilters($request, $query);
        $this->applyDateRangeFilter($request, $query, 'sb.due_date', 'due_date_from', 'due_date_to');

        return $query;
    }
}
