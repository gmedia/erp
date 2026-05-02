<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Models\CustomerInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexArAgingReportAction
{
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = $this->buildQuery();

        $this->applyArAgingReportFilters($request, $query);
        $this->applyRequestSorting(
            $request,
            $query,
            $this->defaultSortBy(),
            $this->sortAliases(),
            $this->plainSortableColumns(),
            $this->aggregateSortableColumns(),
            $this->fallbackSortBy(),
        );

        return $this->exportOrPaginate($request, $query);
    }

    protected function buildQuery(): Builder
    {
        return CustomerInvoice::query()
            ->from('customer_invoices as ci')
            ->join('customers as c', 'ci.customer_id', '=', 'c.id')
            ->leftJoin('branches as b', 'ci.branch_id', '=', 'b.id')
            ->selectRaw($this->compileSelectColumns([
                'ci.id as customer_invoice_id',
                'ci.invoice_number',
                'ci.invoice_date',
                'ci.due_date',
                'ci.grand_total',
                'ci.amount_received',
                'ci.credit_note_amount',
                'ci.amount_due',
                'ci.status',
                'c.id as customer_id',
                'c.name as customer_name',
                'b.id as branch_id',
                'b.name as branch_name',
                $this->agingBucketSelectSql(),
            ]))
            ->withCasts([
                'invoice_date' => 'date',
                'due_date' => 'date',
                'grand_total' => 'decimal:2',
                'amount_received' => 'decimal:2',
                'credit_note_amount' => 'decimal:2',
                'amount_due' => 'decimal:2',
                'aging_current' => 'decimal:2',
                'aging_1_30' => 'decimal:2',
                'aging_31_60' => 'decimal:2',
                'aging_61_90' => 'decimal:2',
                'aging_over_90' => 'decimal:2',
            ]);
    }

    protected function applyArAgingReportFilters(FormRequest $request, Builder $query): void
    {
        $this->applyIntegerFilter($request, $query, 'customer_id', 'ci.customer_id');
        $this->applyIntegerFilter($request, $query, 'branch_id', 'ci.branch_id');
        $this->applyStringFilter($request, $query, 'status', 'ci.status');
        $this->applyDateRangeFilter($request, $query, 'ci.invoice_date');
        $this->applySearchFilter($request, $query, $this->searchColumns());
    }

    /**
     * @return array<int, string>
     */
    protected function searchColumns(): array
    {
        return [
            'ci.invoice_number',
            'c.name',
            'b.name',
        ];
    }

    protected function defaultSortBy(): string
    {
        return 'invoice_date';
    }

    /**
     * @return array<string, string>
     */
    protected function sortAliases(): array
    {
        return [
            'customer_invoice_invoice_number' => 'invoice_number',
            'customer_invoice_invoice_date' => 'invoice_date',
            'customer_invoice_due_date' => 'due_date',
            'customer_invoice_status' => 'status',
            'customer_name' => 'customer_name',
            'branch_name' => 'branch_name',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function plainSortableColumns(): array
    {
        return [
            'invoice_number',
            'invoice_date',
            'due_date',
            'status',
            'customer_name',
            'branch_name',
            'grand_total',
            'amount_received',
            'credit_note_amount',
            'amount_due',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function aggregateSortableColumns(): array
    {
        return [
            'aging_current',
            'aging_1_30',
            'aging_31_60',
            'aging_61_90',
            'aging_over_90',
        ];
    }

    protected function fallbackSortBy(): string
    {
        return $this->defaultSortBy();
    }

    private function agingBucketSelectSql(): string
    {
        return "CASE
            WHEN DATEDIFF(CURDATE(), ci.due_date) <= 0 THEN ci.amount_due
            ELSE 0
        END as aging_current,
        CASE
            WHEN DATEDIFF(CURDATE(), ci.due_date) BETWEEN 1 AND 30 THEN ci.amount_due
            ELSE 0
        END as aging_1_30,
        CASE
            WHEN DATEDIFF(CURDATE(), ci.due_date) BETWEEN 31 AND 60 THEN ci.amount_due
            ELSE 0
        END as aging_31_60,
        CASE
            WHEN DATEDIFF(CURDATE(), ci.due_date) BETWEEN 61 AND 90 THEN ci.amount_due
            ELSE 0
        END as aging_61_90,
        CASE
            WHEN DATEDIFF(CURDATE(), ci.due_date) > 90 THEN ci.amount_due
            ELSE 0
        END as aging_over_90";
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function compileSelectColumns(array $columns): string
    {
        $seen = [];
        $filtered = [];
        foreach ($columns as $col) {
            if (preg_match('/ as ([a-zA-Z0-9_]+)/', $col, $m)) {
                $alias = $m[1];
                if (isset($seen[$alias])) {
                    continue;
                }
                $seen[$alias] = true;
            }
            $filtered[] = $col;
        }

        return implode(",\n                ", $filtered);
    }
}