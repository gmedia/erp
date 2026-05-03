<?php

namespace App\Actions\Reports\Concerns;

use App\Models\CustomerInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

trait BuildsCustomerInvoiceReportQuery
{
    /**
     * Build base customer invoice report query with common joins and columns.
     */
    protected function buildBaseCustomerInvoiceQuery(array $additionalColumns = []): Builder
    {
        $baseColumns = [
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
        ];

        $allColumns = array_merge($baseColumns, $additionalColumns);

        return CustomerInvoice::query()
            ->from('customer_invoices as ci')
            ->join('customers as c', 'ci.customer_id', '=', 'c.id')
            ->leftJoin('branches as b', 'ci.branch_id', '=', 'b.id')
            ->selectRaw($this->compileSelectColumns($allColumns))
            ->withCasts($this->getBaseCasts());
    }

    /**
     * Get base casts for customer invoice report queries.
     *
     * @return array<string, string>
     */
    protected function getBaseCasts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'grand_total' => 'decimal:2',
            'amount_received' => 'decimal:2',
            'credit_note_amount' => 'decimal:2',
            'amount_due' => 'decimal:2',
        ];
    }

    /**
     * Apply common customer invoice report filters.
     */
    protected function applyBaseCustomerInvoiceFilters(FormRequest $request, Builder $query): void
    {
        $this->applyIntegerFilter($request, $query, 'customer_id', 'ci.customer_id');
        $this->applyIntegerFilter($request, $query, 'branch_id', 'ci.branch_id');
        $this->applyStringFilter($request, $query, 'status', 'ci.status');
        $this->applyDateRangeFilter($request, $query, 'ci.invoice_date');
        $this->applySearchFilter($request, $query, $this->getBaseSearchColumns());
    }

    /**
     * Get base search columns for customer invoice reports.
     *
     * @return array<int, string>
     */
    protected function getBaseSearchColumns(): array
    {
        return [
            'ci.invoice_number',
            'c.name',
            'b.name',
        ];
    }

    /**
     * Get base sort aliases for customer invoice reports.
     *
     * @return array<string, string>
     */
    protected function getBaseSortAliases(): array
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
     * Get base plain sortable columns for customer invoice reports.
     *
     * @return array<int, string>
     */
    protected function getBasePlainSortableColumns(): array
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
     * Compile select columns, removing duplicates by alias.
     *
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
