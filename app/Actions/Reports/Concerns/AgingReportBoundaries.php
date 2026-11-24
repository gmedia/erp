<?php

namespace App\Actions\Reports\Concerns;

use App\Actions\AccountingPosting\PostApPaymentJournalAction;
use App\Actions\AccountingPosting\PostArReceiptJournalAction;
use App\Actions\AgingDashboard\GetAgingDashboardDataAction;
use App\Actions\ApPayments\SyncApPaymentAllocationsAction;
use App\Actions\ArReceipts\SyncArReceiptAllocationsAction;
use Illuminate\Support\Carbon;

/**
 * Computes Carbon-based aging bucket boundaries (today, 1, 30, 31, 60, 61, 90)
 * and renders cross-DB safe parameterized SQL fragments for AR/AP aging
 * + outstanding reports.
 *
 * Mirrors the pattern used in {@see GetAgingDashboardDataAction}:
 * date math is performed in PHP (UTC Carbon) and injected via prepared
 * statement bindings instead of MariaDB-only `CURDATE()` / `DATEDIFF()` calls.
 */
trait AgingReportBoundaries
{
    /**
     * @return array{today: string, d1: string, d30: string, d31: string, d60: string, d61: string, d90: string}
     */
    protected function agingBoundaries(?string $asOfDate = null): array
    {
        $today = $asOfDate !== null && $asOfDate !== ''
            ? Carbon::parse($asOfDate)->startOfDay()
            : Carbon::today();

        return [
            'today' => $today->toDateString(),
            'd1' => $today->copy()->subDays(1)->toDateString(),
            'd30' => $today->copy()->subDays(30)->toDateString(),
            'd31' => $today->copy()->subDays(31)->toDateString(),
            'd60' => $today->copy()->subDays(60)->toDateString(),
            'd61' => $today->copy()->subDays(61)->toDateString(),
            'd90' => $today->copy()->subDays(90)->toDateString(),
        ];
    }

    /**
     * Sisa AR per tanggal as_of: grand_total - credit note bertanggal <= as_of
     * - pelunasan posted bertanggal <= as_of (alokasi + diskon). Dipakai bersama
     * oleh aging dan outstanding agar basis historisnya sama.
     * Tanggal berasal dari Carbon::toDateString(), aman disisipkan sebagai literal.
     */
    protected function historicalArDueSql(string $invoiceAlias, string $asOfDate): string
    {
        return "({$invoiceAlias}.grand_total"
            . ' - ' . $this->historicalCreditNoteSql($invoiceAlias, $asOfDate)
            . ' - ' . $this->historicalReceivedSql($invoiceAlias, $asOfDate) . ')';
    }

    /**
     * Pelunasan AR (alokasi + diskon) posted bertanggal <= as_of.
     */
    protected function historicalReceivedSql(string $invoiceAlias, string $asOfDate): string
    {
        $posted = "'" . implode("','", SyncArReceiptAllocationsAction::POSTED_STATUSES) . "'";
        $asOf = "'" . Carbon::parse($asOfDate)->toDateString() . "'";
        $discountAccount = "'" . PostArReceiptJournalAction::AR_DISCOUNT_ACCOUNT_CODE . "'";

        // Diskon hanya ikut bila jurnal receipt memuat baris akun diskon (#437). Data lama
        // yang diskonnya tersimpan tetapi tidak terjurnal tidak mengurangi sisa.
        return 'COALESCE((SELECT SUM(ara.allocated_amount'
            . ' + CASE WHEN EXISTS (SELECT 1 FROM journal_entry_lines jel'
            . ' JOIN accounts acc ON acc.id = jel.account_id'
            . ' WHERE jel.journal_entry_id = arr.journal_entry_id'
            . " AND acc.code = {$discountAccount}) THEN ara.discount_given ELSE 0 END)"
            . ' FROM ar_receipt_allocations ara'
            . ' JOIN ar_receipts arr ON arr.id = ara.ar_receipt_id'
            . " WHERE ara.customer_invoice_id = {$invoiceAlias}.id"
            . " AND arr.status IN ({$posted})"
            . " AND arr.receipt_date <= {$asOf}), 0)";
    }

    /**
     * Credit note AR (confirmed/applied) bertanggal <= as_of.
     */
    protected function historicalCreditNoteSql(string $invoiceAlias, string $asOfDate): string
    {
        $asOf = "'" . Carbon::parse($asOfDate)->toDateString() . "'";

        return 'COALESCE((SELECT SUM(cn.grand_total) FROM credit_notes cn'
            . " WHERE cn.customer_invoice_id = {$invoiceAlias}.id"
            . " AND cn.status IN ('confirmed', 'applied')"
            . " AND cn.credit_note_date <= {$asOf}), 0)";
    }

    /**
     * Sisa AP per tanggal as_of: grand_total - pelunasan posted bertanggal
     * payment_date <= as_of (alokasi + diskon). Pola sama dengan historicalArDueSql.
     */
    protected function historicalApDueSql(string $billAlias, string $asOfDate): string
    {
        $posted = "'" . implode("','", SyncApPaymentAllocationsAction::POSTED_STATUSES) . "'";
        $asOf = "'" . Carbon::parse($asOfDate)->toDateString() . "'";
        $discountAccount = "'" . PostApPaymentJournalAction::AP_DISCOUNT_ACCOUNT_CODE . "'";

        // Diskon hanya ikut bila jurnal payment memuat baris akun diskon (#437).
        return "({$billAlias}.grand_total"
            . ' - COALESCE((SELECT SUM(apa.allocated_amount'
            . ' + CASE WHEN EXISTS (SELECT 1 FROM journal_entry_lines jel'
            . ' JOIN accounts acc ON acc.id = jel.account_id'
            . ' WHERE jel.journal_entry_id = app.journal_entry_id'
            . " AND acc.code = {$discountAccount}) THEN apa.discount_taken ELSE 0 END)"
            . ' FROM ap_payment_allocations apa'
            . ' JOIN ap_payments app ON app.id = apa.ap_payment_id'
            . " WHERE apa.supplier_bill_id = {$billAlias}.id"
            . " AND app.status IN ({$posted})"
            . " AND app.payment_date <= {$asOf}), 0))";
    }

    /**
     * Default AR-style aliases: aging_current, aging_1_30, aging_31_60, aging_61_90, aging_over_90.
     * Pair with {@see agingBucketBindings()} when calling selectRaw().
     */
    protected function agingBucketSelectSql(string $tableAlias, ?string $dueSql = null): string
    {
        return $this->agingBucketSelectSqlWithAliases($tableAlias, [
            'current' => 'aging_current',
            '1_30' => 'aging_1_30',
            '31_60' => 'aging_31_60',
            '61_90' => 'aging_61_90',
            'over_90' => 'aging_over_90',
        ], $dueSql);
    }

    /**
     * Renders the aging bucket CASE expressions with caller-provided column aliases
     * so consumers that need a different output shape (e.g. AP uses
     * `current_amount`, `days_1_30`, etc.) can share the same SQL logic without
     * forcing an API contract change. Pair with {@see agingBucketBindings()}.
     *
     * @param  array{current: string, '1_30': string, '31_60': string, '61_90': string, over_90: string}  $aliases
     */
    protected function agingBucketSelectSqlWithAliases(
        string $tableAlias,
        array $aliases,
        ?string $dueSql = null,
    ): string {
        $aCurrent = $aliases['current'];
        $a1_30 = $aliases['1_30'];
        $a31_60 = $aliases['31_60'];
        $a61_90 = $aliases['61_90'];
        $aOver90 = $aliases['over_90'];
        $due = $dueSql ?? "{$tableAlias}.amount_due";

        return "CASE WHEN {$tableAlias}.due_date >= ?"
            . " THEN {$due} ELSE 0 END as {$aCurrent},"
            . " CASE WHEN {$tableAlias}.due_date BETWEEN ? AND ?"
            . " THEN {$due} ELSE 0 END as {$a1_30},"
            . " CASE WHEN {$tableAlias}.due_date BETWEEN ? AND ?"
            . " THEN {$due} ELSE 0 END as {$a31_60},"
            . " CASE WHEN {$tableAlias}.due_date BETWEEN ? AND ?"
            . " THEN {$due} ELSE 0 END as {$a61_90},"
            . " CASE WHEN {$tableAlias}.due_date < ?"
            . " THEN {$due} ELSE 0 END as {$aOver90}";
    }

    /**
     * @param  array{
     *     today: string, d1: string, d30: string, d31: string, d60: string, d61: string, d90: string
     * }  $boundaries
     * @return list<string>
     */
    protected function agingBucketBindings(array $boundaries): array
    {
        return [
            $boundaries['today'],
            $boundaries['d30'], $boundaries['d1'],
            $boundaries['d60'], $boundaries['d31'],
            $boundaries['d90'], $boundaries['d61'],
            $boundaries['d90'],
        ];
    }
}
