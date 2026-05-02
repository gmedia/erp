<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Models\CustomerInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexArOutstandingReportAction
{
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = $this->buildQuery();

        $this->applyArOutstandingReportFilters($request, $query);
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
                $this->daysOverdueSelectSql(),
            ]))
            ->withCasts([
                'invoice_date' => 'date',
                'due_date' => 'date',
                'grand_total' => 'decimal:2',
                'amount_received' => 'decimal:2',
                'credit_note_amount' => 'decimal:2',
                'amount_due' => 'decimal:2',
                'days_overdue' => 'integer',
            ]);
    }

    protected function applyArOutstandingReportFilters(FormRequest $request, Builder $query): void
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
        return 'days_overdue';
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
            'days_overdue',
        ];
    }

    protected function fallbackSortBy(): string
    {
        return $this->defaultSortBy();
    }

    private function daysOverdueSelectSql(): string
    {
        return "CASE
            WHEN ci.status IN ('sent', 'partially_paid', 'overdue') AND ci.due_date < CURDATE()
            THEN DATEDIFF(CURDATE(), ci.due_date)
            ELSE 0
        END as days_overdue";
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
