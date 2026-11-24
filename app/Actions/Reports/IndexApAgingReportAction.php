<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\AgingReportBoundaries;
use App\Actions\Reports\Concerns\BuildsSupplierBillReportQuery;
use App\Actions\Reports\Concerns\SumsReportTotals;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class IndexApAgingReportAction
{
    use AgingReportBoundaries;
    use BuildsSupplierBillReportQuery;
    use SumsReportTotals;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $this->guardSupplierBillCurrency($request, 'AP Aging Report');

        $query = $this->buildFilteredQuery($request);

        $this->applySupplierBillSorting($request, $query);

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
            'current_amount',
            'days_1_30',
            'days_31_60',
            'days_61_90',
            'days_over_90',
        ], $request->filled('as_of_date') ? $request->string('as_of_date')->toString() : null);
    }

    private function buildFilteredQuery(FormRequest $request): Builder
    {
        $asOfDate = $request->filled('as_of_date')
            ? Carbon::parse($request->string('as_of_date')->toString())->toDateString()
            : Carbon::today()->toDateString();
        $boundaries = $this->agingBoundaries($asOfDate);
        $dueSql = $this->historicalApDueSql('sb', $asOfDate);

        $query = $this->buildBaseSupplierBillQuery(
            extraSelectColumns: [
                $this->agingBucketSelectSqlWithAliases('sb', [
                    'current' => 'current_amount',
                    '1_30' => 'days_1_30',
                    '31_60' => 'days_31_60',
                    '61_90' => 'days_61_90',
                    'over_90' => 'days_over_90',
                ], $dueSql),
            ],
            extraCasts: [
                'current_amount' => 'decimal:2',
                'days_1_30' => 'decimal:2',
                'days_31_60' => 'decimal:2',
                'days_61_90' => 'decimal:2',
                'days_over_90' => 'decimal:2',
            ],
            extraSelectBindings: $this->agingBucketBindings($boundaries),
            // Paid tetap tampil bila sisa historis pada as_of > 0 (lunas setelah as_of).
            statuses: ['confirmed', 'partially_paid', 'overdue', 'paid'],
            amountDueSql: $dueSql,
        );

        $this->applySupplierBillFilters($request, $query);
        $this->applyDateRangeFilter($request, $query, 'sb.bill_date', 'start_date', 'end_date');
        $query->whereDate('sb.bill_date', '<=', $asOfDate)
            ->whereRaw('(' . $dueSql . ') > 0');

        return $query;
    }
}
