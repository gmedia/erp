<?php

namespace App\Actions\Reports\Concerns;

use App\Models\SupplierBill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

trait BuildsSupplierBillReportQuery
{
    use HandlesReportQuery;

    /**
     * @param  array<int, string>  $extraSelectColumns
     * @param  array<string, string>  $extraCasts
     */
    protected function buildBaseSupplierBillQuery(array $extraSelectColumns = [], array $extraCasts = []): Builder
    {
        $baseSelectColumns = [
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
        ];

        $allSelectColumns = array_merge($baseSelectColumns, $extraSelectColumns);

        $baseCasts = [
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'bill_date' => 'date',
            'due_date' => 'date',
        ];

        $allCasts = array_merge($baseCasts, $extraCasts);

        return SupplierBill::query()
            ->from('supplier_bills as sb')
            ->join('suppliers as s', 'sb.supplier_id', '=', 's.id')
            ->join('branches as b', 'sb.branch_id', '=', 'b.id')
            ->selectRaw(implode(",\n                ", $allSelectColumns))
            ->whereIn('sb.status', ['confirmed', 'partially_paid', 'overdue'])
            ->withCasts($allCasts);
    }

    protected function applySupplierBillFilters(FormRequest $request, Builder $query): void
    {
        $this->applyIntegerFilter($request, $query, 'supplier_id', 'sb.supplier_id');
        $this->applyIntegerFilter($request, $query, 'branch_id', 'sb.branch_id');
        $this->applyStringFilter($request, $query, 'status', 'sb.status');
        $this->applySearchFilter($request, $query, ['sb.bill_number', 's.name', 'b.name']);
    }

    /**
     * @param  array<string, string>  $extraSortMap
     */
    protected function applySupplierBillSorting(
        FormRequest $request,
        Builder $query,
        string $defaultSort = 'due_date',
        string $defaultDirection = 'asc',
        array $extraSortMap = []
    ): void {
        $baseSortMap = [
            'supplier_name' => 's.name',
            'branch_name' => 'b.name',
            'bill_number' => 'sb.bill_number',
            'bill_date' => 'sb.bill_date',
            'due_date' => 'sb.due_date',
            'grand_total' => 'sb.grand_total',
            'amount_due' => 'sb.amount_due',
        ];

        $sortMap = array_merge($baseSortMap, $extraSortMap);

        $sortBy = $request->string('sort_by', $defaultSort)->toString();
        $sortDirection = $request->string('sort_direction', $defaultDirection)->toString();
        $resolvedSort = $sortMap[$sortBy] ?? $sortBy;
        $query->orderBy($resolvedSort, $sortDirection);
    }
}
