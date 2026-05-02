<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Models\SupplierBill;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexApOutstandingReportAction
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
                'sb.payment_terms',
                's.id as supplier_id',
                's.name as supplier_name',
                'b.id as branch_id',
                'b.name as branch_name',
                'CASE WHEN sb.due_date < CURDATE() THEN DATEDIFF(CURDATE(), sb.due_date) ELSE 0 END as days_overdue',
            ]))
            ->whereIn('sb.status', ['confirmed', 'partially_paid', 'overdue'])
            ->withCasts([
                'grand_total' => 'decimal:2',
                'amount_paid' => 'decimal:2',
                'amount_due' => 'decimal:2',
                'days_overdue' => 'integer',
                'bill_date' => 'date',
                'due_date' => 'date',
            ]);

        $this->applyIntegerFilter($request, $query, 'supplier_id', 'sb.supplier_id');
        $this->applyIntegerFilter($request, $query, 'branch_id', 'sb.branch_id');
        $this->applyStringFilter($request, $query, 'status', 'sb.status');
        $this->applyDateRangeFilter($request, $query, 'sb.due_date', 'due_date_from', 'due_date_to');
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
            'amount_paid' => 'sb.amount_paid',
            'amount_due' => 'sb.amount_due',
        ];
        $resolvedSort = $sortMap[$sortBy] ?? $sortBy;
        $query->orderBy($resolvedSort, $sortDirection);

        return $this->exportOrPaginate($request, $query);
    }
}
