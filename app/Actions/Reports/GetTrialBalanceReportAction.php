<?php

namespace App\Actions\Reports;

use App\Actions\FiscalYears\GetPreferredFiscalYearAction;
use App\Models\FiscalYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Trial balance detailed dihitung langsung dari journal_entry_lines posted
 * (basis yang sama dengan buku besar dan FinancialReportService), bukan dari
 * account_balances yang hanya terisi saat periode ditutup.
 *
 * - Mutasi: jurnal posted di tahun fiskal pada rentang periode yang dipilih.
 * - Saldo awal akun neraca: semua jurnal posted sebelum awal periode (lintas tahun).
 * - Saldo awal akun laba rugi: hanya jurnal posted di tahun fiskal yang sama.
 * - Jurnal penutup dan pembaliknya ikut dihitung (netto nol setelah tutup-buka-tutup).
 */
class GetTrialBalanceReportAction
{
    private const PNL_TYPES = ['revenue', 'expense'];

    public function execute(array $filters): array
    {
        $fiscalYear = $this->resolveFiscalYear($filters);

        if ($fiscalYear === null) {
            return [
                'data' => [],
                'summary' => [
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'is_balanced' => true,
                ],
            ];
        }

        $periodMonth = $this->intOrNull($filters['period_month'] ?? null);
        $periodYear = $this->intOrNull($filters['period_year'] ?? null);
        [$start, $end] = $this->resolvePeriodRange($fiscalYear, $periodMonth, $periodYear);

        $rows = $this->balancesByAccount($fiscalYear->id, $start, $end)
            ->map(fn (object $balance): array => $this->row($balance, $periodMonth, $periodYear))
            ->values();

        $totalDebit = round((float) $rows->sum('debit_balance'), 2);
        $totalCredit = round((float) $rows->sum('credit_balance'), 2);

        return [
            'data' => $rows,
            'summary' => [
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => bccomp((string) $totalDebit, (string) $totalCredit, 2) === 0,
            ],
        ];
    }

    private function resolveFiscalYear(array $filters): ?FiscalYear
    {
        if (isset($filters['fiscal_year_id']) && $filters['fiscal_year_id'] !== '') {
            return FiscalYear::query()->find((int) $filters['fiscal_year_id']);
        }

        $fiscalYears = FiscalYear::query()->orderByDesc('start_date')->get();

        return app(GetPreferredFiscalYearAction::class)->execute($fiscalYears);
    }

    /**
     * @return array{0: string, 1: string} tanggal awal dan akhir (Y-m-d) rentang mutasi
     */
    private function resolvePeriodRange(FiscalYear $fiscalYear, ?int $month, ?int $year): array
    {
        if ($month === null && $year === null) {
            return [$fiscalYear->start_date->toDateString(), $fiscalYear->end_date->toDateString()];
        }

        $year ??= $fiscalYear->start_date->year;

        if ($month === null) {
            return [Carbon::create($year, 1, 1)->toDateString(), Carbon::create($year, 12, 31)->toDateString()];
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()];
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function balancesByAccount(int $fiscalYearId, string $start, string $end): Collection
    {
        $pnlTypes = self::PNL_TYPES;

        return DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'jel.account_id')
            ->where('je.status', 'posted')
            ->where(function ($query) use ($fiscalYearId, $start, $end, $pnlTypes): void {
                $query->where(function ($inPeriod) use ($fiscalYearId, $end): void {
                    $inPeriod->where('je.fiscal_year_id', $fiscalYearId)
                        ->where('je.entry_date', '<=', $end);
                })->orWhere(function ($carry) use ($start, $pnlTypes): void {
                    $carry->where('je.entry_date', '<', $start)
                        ->whereNotIn('a.type', $pnlTypes);
                });
            })
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.normal_balance')
            ->orderBy('a.code')
            ->select([
                'a.id as account_id',
                'a.code as account_code',
                'a.name as account_name',
                'a.type as account_type',
                'a.normal_balance as normal_balance',
            ])
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN je.entry_date < ? AND (je.fiscal_year_id = ? OR a.type NOT IN (?, ?))
                    THEN jel.debit - jel.credit ELSE 0 END), 0) as opening_net',
                [$start, $fiscalYearId, ...$pnlTypes],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN je.fiscal_year_id = ? AND je.entry_date BETWEEN ? AND ?
                    THEN jel.debit ELSE 0 END), 0) as debit_total',
                [$fiscalYearId, $start, $end],
            )
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN je.fiscal_year_id = ? AND je.entry_date BETWEEN ? AND ?
                    THEN jel.credit ELSE 0 END), 0) as credit_total',
                [$fiscalYearId, $start, $end],
            )
            ->get();
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }

        return (int) $value;
    }

    private function row(object $balance, ?int $periodMonth, ?int $periodYear): array
    {
        // Saldo disimpan dalam konvensi normal balance akun: saldo positif searah normal balance.
        // Untuk akun kredit-normal, saldo positif = kolom kredit.
        $isCreditNormal = $balance->normal_balance === 'credit';
        $sign = $isCreditNormal ? -1 : 1;

        $debitTotal = (float) $balance->debit_total;
        $creditTotal = (float) $balance->credit_total;
        $opening = $sign * (float) $balance->opening_net;
        $closing = $opening + $sign * ($debitTotal - $creditTotal);

        $debitBalance = $isCreditNormal ? -$closing : $closing;

        return [
            'account_id' => $balance->account_id,
            'account_code' => $balance->account_code,
            'account_name' => $balance->account_name,
            'account_type' => $balance->account_type,
            'period_month' => $periodMonth,
            'period_year' => $periodYear,
            'opening_balance' => $this->money($opening),
            'debit_total' => $this->money($debitTotal),
            'credit_total' => $this->money($creditTotal),
            'closing_balance' => $this->money($closing),
            'debit_balance' => $this->money($debitBalance >= 0 ? $debitBalance : 0),
            'credit_balance' => $this->money($debitBalance < 0 ? abs($debitBalance) : 0),
        ];
    }

    /**
     * Bulatkan nominal ke 2 desimal dan normalkan nol negatif (-0.0) menjadi 0.0,
     * agar JSON tidak membawa "-0" yang tampil sebagai "-Rp 0".
     */
    private function money(float $amount): float
    {
        $rounded = round($amount, 2);

        return $rounded == 0.0 ? 0.0 : $rounded;
    }
}
