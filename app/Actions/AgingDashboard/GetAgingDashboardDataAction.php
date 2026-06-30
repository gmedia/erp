<?php

namespace App\Actions\AgingDashboard;

use App\Actions\Concerns\AssertsSingleCurrency;
use App\Models\CustomerInvoice;
use App\Models\SupplierBill;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GetAgingDashboardDataAction
{
    use AssertsSingleCurrency;

    /**
     * Outstanding invoice statuses for AR.
     *
     * @var list<string>
     */
    private const AR_OUTSTANDING_STATUSES = ['sent', 'partially_paid', 'overdue'];

    /**
     * Outstanding bill statuses for AP.
     *
     * @var list<string>
     */
    private const AP_OUTSTANDING_STATUSES = ['confirmed', 'partially_paid', 'overdue'];

    /**
     * @return array{
     *     ar_summary: array{
     *         total_outstanding: float,
     *         current: float,
     *         "1_30": float,
     *         "31_60": float,
     *         "61_90": float,
     *         over_90: float,
     *         overdue_amount: float,
     *         overdue_percentage: float,
     *         invoice_count: int,
     *         overdue_count: int
     *     },
     *     ap_summary: array{
     *         total_outstanding: float,
     *         current: float,
     *         "1_30": float,
     *         "31_60": float,
     *         "61_90": float,
     *         over_90: float,
     *         overdue_amount: float,
     *         overdue_percentage: float,
     *         invoice_count: int,
     *         overdue_count: int
     *     },
     *     ar_buckets: array<int, array{label: string, amount: float, percentage: float}>,
     *     ap_buckets: array<int, array{label: string, amount: float, percentage: float}>,
     *     top_overdue_customers: array<int, array{
     *         customer_id: int,
     *         customer_name: string,
     *         outstanding_amount: float,
     *         overdue_amount: float,
     *         invoice_count: int,
     *         oldest_due_date: ?string,
     *         max_days_overdue: int,
     *     }>,
     *     top_overdue_suppliers: array<int, array{
     *         supplier_id: int,
     *         supplier_name: string,
     *         outstanding_amount: float,
     *         overdue_amount: float,
     *         bill_count: int,
     *         oldest_due_date: ?string,
     *         max_days_overdue: int,
     *     }>
     * }
     */
    public function execute(string $asOfDate, ?int $branchId = null): array
    {
        $asOf = Carbon::parse($asOfDate)->startOfDay();
        $asOfStr = $asOf->toDateString();

        $d1 = $asOf->copy()->subDays(1)->toDateString();
        $d30 = $asOf->copy()->subDays(30)->toDateString();
        $d31 = $asOf->copy()->subDays(31)->toDateString();
        $d60 = $asOf->copy()->subDays(60)->toDateString();
        $d61 = $asOf->copy()->subDays(61)->toDateString();
        $d90 = $asOf->copy()->subDays(90)->toDateString();

        $this->guardHomogeneousCurrency(
            'customer_invoices', self::AR_OUTSTANDING_STATUSES, $branchId, 'aging-dashboard:ar'
        );
        $this->guardHomogeneousCurrency(
            'supplier_bills', self::AP_OUTSTANDING_STATUSES, $branchId, 'aging-dashboard:ap'
        );

        $arRow = $this->aggregateBuckets(
            CustomerInvoice::query()->getQuery(),
            'customer_invoices',
            self::AR_OUTSTANDING_STATUSES,
            $branchId,
            $asOfStr,
            $d1,
            $d30,
            $d31,
            $d60,
            $d61,
            $d90,
        );

        $apRow = $this->aggregateBuckets(
            SupplierBill::query()->getQuery(),
            'supplier_bills',
            self::AP_OUTSTANDING_STATUSES,
            $branchId,
            $asOfStr,
            $d1,
            $d30,
            $d31,
            $d60,
            $d61,
            $d90,
        );

        $arSummary = $this->buildSummary($arRow);
        $apSummary = $this->buildSummary($apRow);

        return [
            'ar_summary' => $arSummary,
            'ap_summary' => $apSummary,
            'ar_buckets' => $this->buildBuckets($arSummary),
            'ap_buckets' => $this->buildBuckets($apSummary),
            'top_overdue_customers' => $this->topOverdueCustomers($branchId, $asOf),
            'top_overdue_suppliers' => $this->topOverdueSuppliers($branchId, $asOf),
        ];
    }

    /**
     * @param  list<string>  $statuses
     * @return object
     */
    private function aggregateBuckets(
        Builder $base,
        string $table,
        array $statuses,
        ?int $branchId,
        string $asOfStr,
        string $d1,
        string $d30,
        string $d31,
        string $d60,
        string $d61,
        string $d90,
    ) {
        $query = DB::table($table)
            ->whereIn('status', $statuses);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        $row = $query->selectRaw(
            'COALESCE(SUM(amount_due), 0) AS total_outstanding,
             COALESCE(SUM(CASE WHEN due_date >= ? THEN amount_due ELSE 0 END), 0) AS current_amount,
             COALESCE(SUM(CASE WHEN due_date BETWEEN ? AND ? THEN amount_due ELSE 0 END), 0) AS amount_1_30,
             COALESCE(SUM(CASE WHEN due_date BETWEEN ? AND ? THEN amount_due ELSE 0 END), 0) AS amount_31_60,
             COALESCE(SUM(CASE WHEN due_date BETWEEN ? AND ? THEN amount_due ELSE 0 END), 0) AS amount_61_90,
             COALESCE(SUM(CASE WHEN due_date < ? THEN amount_due ELSE 0 END), 0) AS amount_over_90,
             COALESCE(SUM(CASE WHEN due_date < ? THEN amount_due ELSE 0 END), 0) AS overdue_amount,
             COUNT(*) AS invoice_count,
             COALESCE(SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END), 0) AS overdue_count',
            [
                $asOfStr,
                $d30, $d1,
                $d60, $d31,
                $d90, $d61,
                $d90,
                $asOfStr,
                $asOfStr,
            ]
        )->first();

        return $row ?? (object) [
            'total_outstanding' => 0,
            'current_amount' => 0,
            'amount_1_30' => 0,
            'amount_31_60' => 0,
            'amount_61_90' => 0,
            'amount_over_90' => 0,
            'overdue_amount' => 0,
            'invoice_count' => 0,
            'overdue_count' => 0,
        ];
    }

    /**
     * @return array{
     *     total_outstanding: float,
     *     current: float,
     *     "1_30": float,
     *     "31_60": float,
     *     "61_90": float,
     *     over_90: float,
     *     overdue_amount: float,
     *     overdue_percentage: float,
     *     invoice_count: int,
     *     overdue_count: int
     * }
     */
    private function buildSummary(object $row): array
    {
        $total = (float) ($row->total_outstanding ?? 0);
        $overdue = (float) ($row->overdue_amount ?? 0);
        $percentage = $total > 0 ? ($overdue / $total) * 100 : 0.0;

        return [
            'total_outstanding' => round($total, 2),
            'current' => round((float) ($row->current_amount ?? 0), 2),
            '1_30' => round((float) ($row->amount_1_30 ?? 0), 2),
            '31_60' => round((float) ($row->amount_31_60 ?? 0), 2),
            '61_90' => round((float) ($row->amount_61_90 ?? 0), 2),
            'over_90' => round((float) ($row->amount_over_90 ?? 0), 2),
            'overdue_amount' => round($overdue, 2),
            'overdue_percentage' => round($percentage, 2),
            'invoice_count' => (int) ($row->invoice_count ?? 0),
            'overdue_count' => (int) ($row->overdue_count ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<int, array{label: string, amount: float, percentage: float}>
     */
    private function buildBuckets(array $summary): array
    {
        $total = (float) $summary['total_outstanding'];

        $entries = [
            ['label' => 'Current', 'amount' => (float) $summary['current']],
            ['label' => '1-30 Days', 'amount' => (float) $summary['1_30']],
            ['label' => '31-60 Days', 'amount' => (float) $summary['31_60']],
            ['label' => '61-90 Days', 'amount' => (float) $summary['61_90']],
            ['label' => 'Over 90 Days', 'amount' => (float) $summary['over_90']],
        ];

        return array_map(function (array $entry) use ($total): array {
            $percentage = $total > 0 ? ($entry['amount'] / $total) * 100 : 0.0;

            return [
                'label' => $entry['label'],
                'amount' => round($entry['amount'], 2),
                'percentage' => round($percentage, 2),
            ];
        }, $entries);
    }

    /**
     * @return array<int, array{
     *     customer_id: int,
     *     customer_name: string,
     *     outstanding_amount: float,
     *     overdue_amount: float,
     *     invoice_count: int,
     *     oldest_due_date: ?string,
     *     max_days_overdue: int,
     * }>
     */
    private function topOverdueCustomers(?int $branchId, Carbon $asOf): array
    {
        $asOfStr = $asOf->toDateString();

        $query = DB::table('customer_invoices as ci')
            ->join('customers as c', 'c.id', '=', 'ci.customer_id')
            ->whereIn('ci.status', self::AR_OUTSTANDING_STATUSES);

        if ($branchId !== null) {
            $query->where('ci.branch_id', $branchId);
        }

        $rows = $query
            ->groupBy('ci.customer_id', 'c.name')
            ->selectRaw(
                'ci.customer_id AS customer_id,
                 c.name AS customer_name,
                 COALESCE(SUM(ci.amount_due), 0) AS outstanding_amount,
                 COALESCE(SUM(CASE WHEN ci.due_date < ? THEN ci.amount_due ELSE 0 END), 0) AS overdue_amount,
                 COUNT(*) AS invoice_count,
                 MIN(ci.due_date) AS oldest_due_date',
                [$asOfStr]
            )
            ->havingRaw('SUM(CASE WHEN ci.due_date < ? THEN ci.amount_due ELSE 0 END) > 0', [$asOfStr])
            ->orderByDesc('overdue_amount')
            ->orderByDesc('outstanding_amount')
            ->limit(10)
            ->get();

        return $rows->map(function ($row) use ($asOf): array {
            $oldest = $row->oldest_due_date ? Carbon::parse($row->oldest_due_date) : null;
            $maxDaysOverdue = $oldest && $oldest->lt($asOf)
                ? (int) $oldest->diffInDays($asOf)
                : 0;

            return [
                'customer_id' => (int) $row->customer_id,
                'customer_name' => (string) $row->customer_name,
                'outstanding_amount' => round((float) $row->outstanding_amount, 2),
                'overdue_amount' => round((float) $row->overdue_amount, 2),
                'invoice_count' => (int) $row->invoice_count,
                'oldest_due_date' => $oldest?->toDateString(),
                'max_days_overdue' => $maxDaysOverdue,
            ];
        })->all();
    }

    /**
     * @return array<int, array{
     *     supplier_id: int,
     *     supplier_name: string,
     *     outstanding_amount: float,
     *     overdue_amount: float,
     *     bill_count: int,
     *     oldest_due_date: ?string,
     *     max_days_overdue: int,
     * }>
     */
    private function topOverdueSuppliers(?int $branchId, Carbon $asOf): array
    {
        $asOfStr = $asOf->toDateString();

        $query = DB::table('supplier_bills as sb')
            ->join('suppliers as s', 's.id', '=', 'sb.supplier_id')
            ->whereIn('sb.status', self::AP_OUTSTANDING_STATUSES);

        if ($branchId !== null) {
            $query->where('sb.branch_id', $branchId);
        }

        $rows = $query
            ->groupBy('sb.supplier_id', 's.name')
            ->selectRaw(
                'sb.supplier_id AS supplier_id,
                 s.name AS supplier_name,
                 COALESCE(SUM(sb.amount_due), 0) AS outstanding_amount,
                 COALESCE(SUM(CASE WHEN sb.due_date < ? THEN sb.amount_due ELSE 0 END), 0) AS overdue_amount,
                 COUNT(*) AS bill_count,
                 MIN(sb.due_date) AS oldest_due_date',
                [$asOfStr]
            )
            ->havingRaw('SUM(CASE WHEN sb.due_date < ? THEN sb.amount_due ELSE 0 END) > 0', [$asOfStr])
            ->orderByDesc('overdue_amount')
            ->orderByDesc('outstanding_amount')
            ->limit(10)
            ->get();

        return $rows->map(function ($row) use ($asOf): array {
            $oldest = $row->oldest_due_date ? Carbon::parse($row->oldest_due_date) : null;
            $maxDaysOverdue = $oldest && $oldest->lt($asOf)
                ? (int) $oldest->diffInDays($asOf)
                : 0;

            return [
                'supplier_id' => (int) $row->supplier_id,
                'supplier_name' => (string) $row->supplier_name,
                'outstanding_amount' => round((float) $row->outstanding_amount, 2),
                'overdue_amount' => round((float) $row->overdue_amount, 2),
                'bill_count' => (int) $row->bill_count,
                'oldest_due_date' => $oldest?->toDateString(),
                'max_days_overdue' => $maxDaysOverdue,
            ];
        })->all();
    }

    /**
     * @param  list<string>  $statuses
     */
    private function guardHomogeneousCurrency(
        string $table,
        array $statuses,
        ?int $branchId,
        string $context,
    ): void {
        $query = DB::table($table)->whereIn('status', $statuses);

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        $this->assertSingleCurrency($query, $context);
    }
}
