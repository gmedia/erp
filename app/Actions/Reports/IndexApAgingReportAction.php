<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Models\SupplierBill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexApAgingReportAction
{
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = SupplierBill::query()
            ->from('supplier_bills as sb')
            ->join('suppliers as s', 'sb.supplier_id', '=', 's.id')
            ->join('branches as b', 'sb.branch_id', '=', 'b.id')
            ->selectRaw(implode(",\n                ", [
                'sb.id',
                'sb.bill_number',
                'sb.bill_date',
                'sb.due_date',
                'sb.grand_total',
                'sb.amount_paid',
                'sb.amount_due',
                'sb.status',
                'sb.currency',
                's.id as supplier_id',
                's.name as supplier_name',
                'b.id as branch_id',
                'b.name as branch_name',
                'CASE WHEN sb.due_date >= CURDATE() THEN sb.amount_due ELSE 0 END as current_amount',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) BETWEEN 1 AND 30 THEN sb.amount_due ELSE 0 END as days_1_30',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) BETWEEN 31 AND 60 THEN sb.amount_due ELSE 0 END as days_31_60',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) BETWEEN 61 AND 90 THEN sb.amount_due ELSE 0 END as days_61_90',
                'CASE WHEN DATEDIFF(CURDATE(), sb.due_date) > 90 THEN sb.amount_due ELSE 0 END as days_over_90',
            ]))
            ->whereIn('sb.status', ['confirmed', 'partially_paid', 'overdue'])
            ->withCasts([
                'grand_total' => 'decimal:2',
                'amount_paid' => 'decimal:2',
                'amount_due' => 'decimal:2',
                'current_amount' => 'decimal:2',
                'days_1_30' => 'decimal:2',
                'days_31_60' => 'decimal:2',
                'days_61_90' => 'decimal:2',
                'days_over_90' => 'decimal:2',
                'bill_date' => 'date',
                'due_date' => 'date',
            ]);

        $this->applyIntegerFilter($request, $query, 'supplier_id', 'sb.supplier_id');
        $this->applyIntegerFilter($request, $query, 'branch_id', 'sb.branch_id');
        $this->applyStringFilter($request, $query, 'status', 'sb.status');
        $this->applyDateRangeFilter($request, $query, 'sb.bill_date', 'start_date', 'end_date');
        $this->applySearchFilter($request, $query, ['sb.bill_number', 's.name', 'b.name']);

        $sortBy = $request->string('sort_by', 'due_date')->toString();
        $sortDirection = $request->string('sort_direction', 'asc')->toString();
        $sortMap = [
            'supplier_name' => 's.name',
            'branch_name' => 'b.name',
            'bill_number' => 'sb.bill_number',
            'bill_date' => 'sb.bill_date',
            'due_date' => 'sb.due_date',
            'grand_total' => 'sb.grand_total',
            'amount_due' => 'sb.amount_due',
        ];
        $resolvedSort = $sortMap[$sortBy] ?? $sortBy;
        $query->orderBy($resolvedSort, $sortDirection);

        return $this->exportOrPaginate($request, $query);
    }
}
