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
                'CASE WHEN sb.due_date >= ? THEN sb.amount_due ELSE 0 END as current_amount',
                'CASE WHEN sb.due_date BETWEEN ? AND ? THEN sb.amount_due ELSE 0 END as days_1_30',
                'CASE WHEN sb.due_date BETWEEN ? AND ? THEN sb.amount_due ELSE 0 END as days_31_60',
                'CASE WHEN sb.due_date BETWEEN ? AND ? THEN sb.amount_due ELSE 0 END as days_61_90',
                'CASE WHEN sb.due_date < ? THEN sb.amount_due ELSE 0 END as days_over_90',
            ],
            extraCasts: [
                'current_amount' => 'decimal:2',
                'days_1_30' => 'decimal:2',
                'days_31_60' => 'decimal:2',
                'days_61_90' => 'decimal:2',
                'days_over_90' => 'decimal:2',
            ],
            extraSelectBindings: [
                $boundaries['today'],
                $boundaries['d30'], $boundaries['d1'],
                $boundaries['d60'], $boundaries['d31'],
                $boundaries['d90'], $boundaries['d61'],
                $boundaries['d90'],
            ],
        );

        $this->applySupplierBillFilters($request, $query);
        $this->applyDateRangeFilter($request, $query, 'sb.bill_date', 'start_date', 'end_date');
        $this->applySupplierBillSorting($request, $query);

        return $this->exportOrPaginate($request, $query);
    }
}
