<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\AgingReportBoundaries;
use App\Actions\Reports\Concerns\BuildsSupplierBillReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexApAgingReportAction
{
    use AgingReportBoundaries;
    use BuildsSupplierBillReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $boundaries = $this->agingBoundaries();

        $query = $this->buildBaseSupplierBillQuery(
            extraSelectColumns: [
                $this->agingBucketSelectSqlWithAliases('sb', [
                    'current' => 'current_amount',
                    '1_30' => 'days_1_30',
                    '31_60' => 'days_31_60',
                    '61_90' => 'days_61_90',
                    'over_90' => 'days_over_90',
                ]),
            ],
            extraCasts: [
                'current_amount' => 'decimal:2',
                'days_1_30' => 'decimal:2',
                'days_31_60' => 'decimal:2',
                'days_61_90' => 'decimal:2',
                'days_over_90' => 'decimal:2',
            ],
            extraSelectBindings: $this->agingBucketBindings($boundaries),
        );

        $this->applySupplierBillFilters($request, $query);
        $this->applyDateRangeFilter($request, $query, 'sb.bill_date', 'start_date', 'end_date');
        $this->applySupplierBillSorting($request, $query);

        return $this->exportOrPaginate($request, $query);
    }
}
