<?php

namespace App\Actions\Reports;

use App\Actions\ArReceipts\SyncArReceiptAllocationsAction;
use App\Actions\Reports\Concerns\AgingReportBoundaries;
use App\Actions\Reports\Concerns\BuildsCustomerInvoiceReportQuery;
use App\Actions\Reports\Concerns\HandlesReportQuery;
use App\Actions\Reports\Concerns\SumsReportTotals;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class IndexArAgingReportAction
{
    use AgingReportBoundaries;
    use BuildsCustomerInvoiceReportQuery;
    use HandlesReportQuery;
    use SumsReportTotals;

    public function execute(FormRequest $request): LengthAwarePaginator|Collection
    {
        $this->guardCustomerInvoiceCurrency($request, 'AR Aging Report');

        $query = $this->buildQuery($this->resolveAsOfDate($request));

        $this->applyBaseCustomerInvoiceFilters($request, $query);
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

    /**
     * Total + as-of untuk seluruh hasil filter (bukan satu halaman).
     *
     * @return array{as_of_date: string, totals: array<string, string>}
     */
    public function summarize(FormRequest $request): array
    {
        $asOfDate = $this->resolveAsOfDate($request);
        $query = $this->buildQuery($asOfDate);

        $this->applyBaseCustomerInvoiceFilters($request, $query);

        return $this->buildReportSummary($query, [
            'grand_total',
            'amount_received',
            'credit_note_amount',
            'amount_due',
            'aging_current',
            'aging_1_30',
            'aging_31_60',
            'aging_61_90',
            'aging_over_90',
        ], $asOfDate);
    }

    /**
     * Tanggal basis aging: hari ini bila tidak diisi. Semua dokumen dan
     * pelunasan setelah tanggal ini tidak ikut dihitung.
     */
    protected function resolveAsOfDate(FormRequest $request): string
    {
        return $request->filled('as_of_date')
            ? Carbon::parse($request->string('as_of_date')->toString())->toDateString()
            : Carbon::today()->toDateString();
    }

    protected function buildQuery(?string $asOfDate = null): Builder
    {
        $asOfDate ??= Carbon::today()->toDateString();
        $boundaries = $this->agingBoundaries($asOfDate);
        $dueSql = $this->historicalDueSql($asOfDate);

        return $this->buildBaseCustomerInvoiceQuery(
            [$this->agingBucketSelectSql('ci', $dueSql)],
            $this->agingBucketBindings($boundaries),
            $dueSql,
        )
            // Aging hanya piutang berjalan; paid/cancelled/void/draft bukan
            // saldo tagihan (W5-14, sejajar AR Outstanding).
            ->whereIn('ci.status', ['sent', 'partially_paid', 'overdue'])
            ->whereDate('ci.invoice_date', '<=', $asOfDate)
            ->whereRaw('(' . $dueSql . ') > 0')
            ->withCasts(array_merge($this->getBaseCasts(), [
                'aging_current' => 'decimal:2',
                'aging_1_30' => 'decimal:2',
                'aging_31_60' => 'decimal:2',
                'aging_61_90' => 'decimal:2',
                'aging_over_90' => 'decimal:2',
            ]));
    }

    /**
     * Sisa tagihan per tanggal as_of, dihitung ulang dari pelunasan posted yang
     * bertanggal <= as_of (bukan dari amount_due hari ini). Nilai tanggal berasal
     * dari Carbon::toDateString(), jadi aman disisipkan sebagai literal.
     */
    protected function historicalDueSql(string $asOfDate): string
    {
        $posted = "'" . implode("','", SyncArReceiptAllocationsAction::POSTED_STATUSES) . "'";
        $asOf = "'" . Carbon::parse($asOfDate)->toDateString() . "'";

        return '(ci.grand_total - ci.credit_note_amount - COALESCE(('
            . 'SELECT SUM(ara.allocated_amount + ara.discount_given)'
            . ' FROM ar_receipt_allocations ara'
            . ' JOIN ar_receipts arr ON arr.id = ara.ar_receipt_id'
            . ' WHERE ara.customer_invoice_id = ci.id'
            . " AND arr.status IN ({$posted})"
            . " AND arr.receipt_date <= {$asOf}"
            . '), 0))';
    }

    protected function defaultSortBy(): string
    {
        return 'invoice_date';
    }

    protected function sortAliases(): array
    {
        return $this->getBaseSortAliases();
    }

    protected function plainSortableColumns(): array
    {
        return $this->getBasePlainSortableColumns();
    }

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
}
