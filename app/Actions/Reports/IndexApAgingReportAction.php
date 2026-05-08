<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\BuildsSupplierBillReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexApAgingReportAction
{
    use BuildsSupplierBillReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = $this->buildBaseSupplierBillQuery(
            extraSelectColumns: [
                'CASE WHEN sb.due_date >= CURDATE() THEN sb.amount_due ELSE 0 END as current_amount',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) BETWEEN 1 AND 30 THEN sb.amount_due ELSE 0 END as days_1_30',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) BETWEEN 31 AND 60 THEN sb.amount_due ELSE 0 END as days_31_60',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) BETWEEN 61 AND 90 THEN sb.amount_due ELSE 0 END as days_61_90',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) > 90 THEN sb.amount_due ELSE 0 END as days_over_90',
            ],
            extraCasts: [
                'current_amount' => 'decimal:2',
                'days_1_30' => 'decimal:2',
                'days_31_60' => 'decimal:2',
                'days_61_90' => 'decimal:2',
                'days_over_90' => 'decimal:2',
            ],
        );

        $this->applySupplierBillFilters($request, $query);
        $this->applyDateRangeFilter($request, $query, 'sb.bill_date', 'start_date', 'end_date');
        $this->applySupplierBillSorting($request, $query);

        return $this->exportOrPaginate($request, $query);
    }
}
