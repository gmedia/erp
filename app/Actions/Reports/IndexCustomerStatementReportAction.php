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
        $query = $this->buildQuery($request);

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

    protected function buildQuery(FormRequest $request): Builder
    {
        $startDate = $request->filled('start_date') ? $request->string('start_date')->toString() : null;

        return $this->buildBaseCustomerInvoiceQuery(
            [$this->runningBalanceSelectSql($startDate)],
            $startDate !== null ? [$startDate] : [],
        )
            // Running balance hanya transaksi piutang berjalan (W5-14).
            ->whereIn('ci.status', ['sent', 'partially_paid', 'overdue'])
            ->where('ci.amount_due', '>', 0)
            ->withCasts(array_merge($this->getBaseCasts(), [
                'running_balance' => 'decimal:2',
            ]));
    }

    protected function applyCustomerStatementReportFilters(FormRequest $request, Builder $query): void
    {
        if ($request->filled('customer_id')) {
            $query->where('ci.customer_id', $request->integer('customer_id'));
        }

        if ($request->integer('branch_id') > 0) {
            $query->where('ci.branch_id', $request->integer('branch_id'));
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
            'customer_name' => 'customer_name',
            'branch_name' => 'branch_name',
        ];
    }

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

    /**
     * Saldo berjalan per customer. Bila start_date diisi, piutang berjalan sebelum
     * start_date (saldo awal) ikut dijumlahkan sebagai titik awal.
     */
    private function runningBalanceSelectSql(?string $startDate): string
    {
        $window = 'SUM(ci.amount_due) OVER (
            PARTITION BY ci.customer_id
            ORDER BY ci.invoice_date, ci.id
            ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
        )';

        if ($startDate === null) {
            return $window . ' as running_balance';
        }

        $opening = 'COALESCE((
            SELECT SUM(prior.amount_due)
            FROM customer_invoices AS prior
            WHERE prior.customer_id = ci.customer_id
              AND prior.status IN (\'sent\', \'partially_paid\', \'overdue\')
              AND prior.amount_due > 0
              AND prior.invoice_date < ?
        ), 0)';

        return $opening . ' + ' . $window . ' as running_balance';
    }
}
