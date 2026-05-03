<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\BuildsCustomerInvoiceReportQuery;
use App\Actions\Reports\Concerns\HandlesReportQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class IndexCustomerStatementReportAction
{
    use BuildsCustomerInvoiceReportQuery;
    use HandlesReportQuery;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $query = $this->buildQuery();

        $this->applyCustomerStatementReportFilters($request, $query);
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
        return $this->buildBaseCustomerInvoiceQuery([$this->runningBalanceSelectSql()])
            ->withCasts(array_merge($this->getBaseCasts(), [
                'running_balance' => 'decimal:2',
            ]));
    }

    protected function applyCustomerStatementReportFilters(FormRequest $request, Builder $query): void
    {
        if ($request->filled('customer_id')) {
            $query->where('ci.customer_id', $request->integer('customer_id'));
        }

        $this->applyDateRangeFilter($request, $query, 'ci.invoice_date');
        $this->applySearchFilter($request, $query, ['ci.invoice_number']);
    }

    protected function defaultSortBy(): string
    {
        return 'invoice_date';
    }

    protected function sortAliases(): array
    {
        return [
            'customer_invoice_invoice_number' => 'invoice_number',
            'customer_invoice_invoice_date' => 'invoice_date',
            'customer_invoice_due_date' => 'due_date',
            'customer_invoice_status' => 'status',
        ];
    }

    protected function plainSortableColumns(): array
    {
        return [
            'invoice_number',
            'invoice_date',
            'due_date',
            'status',
            'grand_total',
            'amount_received',
            'credit_note_amount',
            'amount_due',
        ];
    }

    protected function aggregateSortableColumns(): array
    {
        return [
            'running_balance',
        ];
    }

    protected function fallbackSortBy(): string
    {
        return $this->defaultSortBy();
    }

    private function runningBalanceSelectSql(): string
    {
        return 'SUM(ci.amount_due) OVER (
            PARTITION BY ci.customer_id
            ORDER BY ci.invoice_date, ci.id
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        ) as running_balance';
    }
}
