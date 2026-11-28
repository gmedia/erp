<?php

namespace App\Services;

use App\Actions\AccountingPosting\PostAssetDisposalJournalAction;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntryLine;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /**
     * Get Trial Balance Report
     */
    public function getTrialBalance(int $fiscalYearId, ?int $branchId = null): array
    {
        $coaVersion = $this->resolveRequiredCoaVersion($fiscalYearId);

        if (! $coaVersion) {
            return [];
        }

        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId, $branchId)
            ->orderBy('code')
            ->get();

        return $this->mapAccountsWithNetMovement(
            $accounts,
            fn (Account $account, float $debit, float $credit, float $netDebit, float $netCredit) => [
                ...$this->baseAccountPayload($account),
                'debit' => $netDebit,
                'credit' => $netCredit,
                'raw_debit' => $debit,
                'raw_credit' => $credit,
            ]
        );
    }

    /**
     * Total trial balance untuk kartu ringkasan: jumlah debit dan kredit net per
     * akun pada jurnal posted, mengikuti filter cabang. Bentuk `report` (list)
     * tidak berubah; total ini hanya dipasok ke EvaluateReportSectionsAction.
     *
     * @return array{total_debit: float, total_credit: float}
     */
    public function getTrialBalanceTotals(int $fiscalYearId, ?int $branchId = null): array
    {
        $rows = $this->getTrialBalance($fiscalYearId, $branchId);

        return [
            'total_debit' => round((float) array_sum(array_column($rows, 'debit')), 2),
            'total_credit' => round((float) array_sum(array_column($rows, 'credit')), 2),
        ];
    }

    public function getCashFlow(int $fiscalYearId, ?int $branchId = null): array
    {
        $coaVersion = $this->resolveRequiredCoaVersion($fiscalYearId);

        if (! $coaVersion) {
            return [];
        }

        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId, $branchId, null, false, true)
            ->where('is_cash_flow', true)
            ->orderBy('code')
            ->get();

        return $this->mapAccountsWithNetMovement(
            $accounts,
            fn (Account $account, float $debit, float $credit, float $netDebit, float $netCredit) => [
                ...$this->baseAccountPayload($account),
                // Arus kas bruto: debit = kas masuk, kredit = kas keluar, agar
                // pembayaran AP tetap terlihat walau ada penerimaan (W6-06).
                'inflow' => $debit,
                'outflow' => $credit,
            ]
        );
    }

    /**
     * Total arus kas untuk ringkasan laporan (dipakai config cash_flow).
     *
     * @return array{cash_inflow: float, cash_outflow: float, net_change: float,
     *     operating_cash_flow: float, investing_cash_flow: float, financing_cash_flow: float}
     */
    public function getCashFlowTotals(int $fiscalYearId, ?int $branchId = null): array
    {
        $rows = $this->getCashFlow($fiscalYearId, $branchId);

        $inflow = array_sum(array_column($rows, 'inflow'));
        $outflow = array_sum(array_column($rows, 'outflow'));
        $activities = $this->getCashFlowActivityTotals($fiscalYearId, $branchId);

        return [
            'cash_inflow' => round($inflow, 2),
            'cash_outflow' => round($outflow, 2),
            // Independen dari klasifikasi: perubahan kas langsung dari baris akun kas.
            'net_change' => $this->getCashNetChange($fiscalYearId, $branchId),
            'operating_cash_flow' => $activities['operating'],
            'investing_cash_flow' => $activities['investing'],
            'financing_cash_flow' => $activities['financing'],
        ];
    }

    /**
     * Perubahan kas periode: jumlah (debit - kredit) seluruh baris akun kas pada
     * jurnal posted tahun fiskal. Himpunan jurnal sama dengan klasifikasi aktivitas.
     */
    public function getCashNetChange(int $fiscalYearId, ?int $branchId = null): float
    {
        $balance = JournalEntryLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts as line_accounts', 'line_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYearId)
            ->where('journal_entries.status', 'posted')
            ->where('line_accounts.is_cash_flow', true)
            ->when($branchId !== null, fn ($q) => $q->where('journal_entry_lines.branch_id', $branchId))
            ->tap(fn ($query) => $this->excludeOpeningBalanceEntries($query))
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0) as balance')
            ->value('balance');

        return round((float) $balance, 2);
    }

    /**
     * Saldo kas awal periode: jumlah mutasi kas posted sebelum tanggal mulai
     * tahun fiskal. Tanpa kolom saldo awal baru, sehingga tidak perlu migrasi.
     */
    public function getOpeningCashBalance(int $fiscalYearId, ?int $branchId = null): float
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        // Lintas versi COA: jurnal tahun lalu memakai akun versi lama, jadi akun
        // kas dikenali dari flag is_cash_flow di versi mana pun, bukan versi terpilih.
        $balance = JournalEntryLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts as line_accounts', 'line_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.entry_date', '<', $fiscalYear->start_date->toDateString())
            ->where('line_accounts.is_cash_flow', true)
            ->when($branchId !== null, fn ($q) => $q->where('journal_entry_lines.branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit - journal_entry_lines.credit), 0) as balance')
            ->value('balance');

        return round((float) $balance, 2);
    }

    /**
     * Klasifikasi arus kas per aktivitas (operasi, investasi, pendanaan).
     *
     * Setiap jurnal posted yang menyentuh akun kas dipecah per baris lawan:
     * kontribusi baris lawan = kredit - debit (kas masuk bernilai positif).
     * Karena jurnal seimbang, jumlah kontribusi sama dengan perubahan kas.
     * Baris lawan yang juga akun kas (transfer antar kas) saling meniadakan.
     *
     * Kelompok ditentukan dari akun lawan:
     * - investasi: akun laba/rugi pelepasan aset (pengaturan), atau sub_type
     *   non_current_asset (aset tetap & akumulasi penyusutan)
     * - pendanaan: sub_type non_current_liability atau type equity
     * - operasi: selain itu
     *
     * @return array{operating: float, investing: float, financing: float}
     */
    public function getCashFlowActivityTotals(int $fiscalYearId, ?int $branchId = null): array
    {
        $totals = ['operating' => 0.0, 'investing' => 0.0, 'financing' => 0.0];
        $disposalAccountIds = $this->assetDisposalAccountIds();

        // Himpunan jurnal sama dengan net_change (tanpa filter versi COA pada baris
        // lawan), agar jumlah aktivitas selalu sama dengan perubahan kas.
        // Query builder (bukan model) agar tipe baris jelas dan atribut `type`
        // pada JournalEntryLine yang di-override accessor tidak ikut terbaca.
        $lines = JournalEntryLine::query()
            ->toBase()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts as line_accounts', 'line_accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYearId)
            ->where('journal_entries.status', 'posted')
            ->when($branchId !== null, fn ($q) => $q->where('journal_entry_lines.branch_id', $branchId))
            ->tap(fn ($query) => $this->excludeOpeningBalanceEntries($query))
            ->get([
                'journal_entry_lines.journal_entry_id',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
                'line_accounts.is_cash_flow',
                'line_accounts.id as account_id',
                'line_accounts.type as account_type',
                'line_accounts.sub_type as account_sub_type',
            ]);

        foreach ($lines->groupBy('journal_entry_id') as $journalLines) {
            $hasCashLine = $journalLines->contains(fn (object $line): bool => (bool) $line->is_cash_flow);
            if (! $hasCashLine) {
                continue;
            }

            $counterLines = $journalLines->reject(fn (object $line): bool => (bool) $line->is_cash_flow);

            foreach ($counterLines as $counter) {
                $contribution = (float) $counter->credit - (float) $counter->debit;
                if ($contribution === 0.0) {
                    continue;
                }

                $activity = in_array((int) $counter->account_id, $disposalAccountIds, true)
                    ? 'investing'
                    : $this->classifyCashFlowActivity($counter->account_type, $counter->account_sub_type);
                $totals[$activity] += $contribution;
            }
        }

        return [
            'operating' => round($totals['operating'], 2),
            'investing' => round($totals['investing'], 2),
            'financing' => round($totals['financing'], 2),
        ];
    }

    /**
     * Get monthly revenue vs expense trends for a fiscal year.
     *
     * @return array<int, array{month: int, label: string, revenue: float, expenses: float, net_income: float}>
     */
    public function getMonthlyTrends(int $fiscalYearId, ?int $branchId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (! $coaVersion) {
            return [];
        }

        // PHP-side month bucketing keeps query cross-DB safe (avoids MariaDB-only MONTH()).
        /** @var Collection<int, object{entry_date: string, account_type: string, debit: string, credit: string}> */
        $rows = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYearId)
            ->where('journal_entries.status', 'posted')
            ->where('accounts.coa_version_id', $coaVersion->id)
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->when($branchId !== null, fn ($query) => $query->where('journal_entry_lines.branch_id', $branchId))
            ->select([
                'journal_entries.entry_date as entry_date',
                'accounts.type as account_type',
                'journal_entry_lines.debit as debit',
                'journal_entry_lines.credit as credit',
            ])
            ->get();

        $dataByMonth = [];
        foreach ($rows as $row) {
            $month = Carbon::parse((string) $row->entry_date)->month;
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;

            $dataByMonth[$month] ??= ['revenue' => 0.0, 'expenses' => 0.0];

            if ($row->account_type === 'revenue') {
                $dataByMonth[$month]['revenue'] += $credit - $debit;
            } else {
                $dataByMonth[$month]['expenses'] += $debit - $credit;
            }
        }

        $months = [];
        $startMonth = (int) $fiscalYear->start_date->format('n');

        for ($i = 0; $i < 12; $i++) {
            $monthNum = (($startMonth - 1 + $i) % 12) + 1;
            $revenue = $dataByMonth[$monthNum]['revenue'] ?? 0.0;
            $expenses = $dataByMonth[$monthNum]['expenses'] ?? 0.0;

            $months[] = [
                'month' => $monthNum,
                'label' => date('M', mktime(0, 0, 0, $monthNum, 1)),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_income' => $revenue - $expenses,
            ];
        }

        return $months;
    }

    /**
     * Get Balance Sheet Report
     */
    public function getBalanceSheet(
        int $fiscalYearId,
        ?int $comparisonFiscalYearId = null,
        ?int $branchId = null,
    ): array {
        $coaVersion = $this->resolveRequiredCoaVersion($fiscalYearId);

        if (! $coaVersion) {
            return $this->emptyBalanceSheetReport();
        }

        // 1. Get Asset, Liability, Equity Accounts
        // We need to fetch balances for BOTH years.
        // Complex part: COA might be different.
        // For this task, we assume we display the CURRENT COA structure,
        // and map previous years data to it IF possible (via mapping or just same code).
        // Design doc says: "Sistem akan mencoba mencocokkan akun berdasarkan kolom code."

        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId, $branchId)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->orderBy('code')
            ->get();

        [
            'comparisonCoaVersion' => $comparisonCoaVersion,
            'comparisonBalanceByCurrentAccountId' => $comparisonBalanceByCurrentAccountId,
        ] = $this->prepareComparisonContext($accounts, $comparisonFiscalYearId, $branchId);

        // 2. Calculate Net Income for Current Year
        $netIncome = $this->calculateNetIncome($fiscalYearId, $coaVersion->id, $branchId);

        // 3. Calculate Net Income for Comparison Year (if exists)
        $comparisonNetIncome = 0;
        if ($comparisonFiscalYearId && $comparisonCoaVersion) {
            $comparisonNetIncome = $this->calculateNetIncome(
                $comparisonFiscalYearId,
                $comparisonCoaVersion->id,
                $branchId,
            );
        }

        $clearingReclass = $this->extractClearingReclassification(
            $accounts,
            $branchId,
            $comparisonFiscalYearId,
            $comparisonBalanceByCurrentAccountId,
        );
        $accounts = $clearingReclass['accounts'];

        ['buckets' => $buckets, 'totals' => $totals] = $this->collectAccountBuckets(
            $accounts,
            $comparisonFiscalYearId,
            $comparisonBalanceByCurrentAccountId,
            [
                'asset' => ['bucket' => 'assets', 'total' => 'assets'],
                'liability' => ['bucket' => 'liabilities', 'total' => 'liabilities'],
                'equity' => ['bucket' => 'equity', 'total' => 'equity'],
            ]
        );

        if ($clearingReclass['item'] !== null) {
            $bucket = $clearingReclass['bucket'];
            $buckets[$bucket][] = $clearingReclass['item'];
            $totals[$bucket] += $clearingReclass['item']['balance'];
            $totals["comparison_{$bucket}"] += $clearingReclass['item']['comparison_balance'];
        }

        // Add Current Year Earnings to Equity
        $this->appendCurrentYearEarnings($buckets, $totals, $netIncome, $comparisonNetIncome);

        $this->appendChangeMetrics($totals, ['assets', 'liabilities', 'equity']);

        return [
            'assets' => $this->buildTree($buckets['assets']),
            'liabilities' => $this->buildTree($buckets['liabilities']),
            'equity' => $this->buildTree($buckets['equity']),
            'totals' => $totals,
        ];
    }

    /**
     * @param  bool  $withoutDepartment  Hanya baris jurnal tanpa departemen (department_id IS NULL).
     *                                   Tidak bisa dikombinasikan dengan $departmentId.
     */
    public function getIncomeStatement(
        int $fiscalYearId,
        ?int $comparisonFiscalYearId = null,
        ?int $branchId = null,
        ?int $departmentId = null,
        bool $withoutDepartment = false,
    ): array {
        $coaVersion = $this->resolveRequiredCoaVersion($fiscalYearId);

        if (! $coaVersion) {
            return $this->emptyIncomeStatementReport();
        }

        $departmentFilter = ['departmentId' => $departmentId, 'withoutDepartment' => $withoutDepartment];

        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums(
            $coaVersion->id,
            $fiscalYearId,
            $branchId,
            $departmentId,
            $withoutDepartment,
        )
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('code')
            ->get();

        ['comparisonBalanceByCurrentAccountId' => $comparisonBalanceByCurrentAccountId]
            = $this->prepareComparisonContext($accounts, $comparisonFiscalYearId, $branchId, $departmentFilter);

        ['buckets' => $buckets, 'totals' => $totals] = $this->collectAccountBuckets(
            $accounts,
            $comparisonFiscalYearId,
            $comparisonBalanceByCurrentAccountId,
            [
                'revenue' => ['bucket' => 'revenues', 'total' => 'revenue'],
                'expense' => ['bucket' => 'expenses', 'total' => 'expense'],
            ]
        );

        $totals['net_income'] = $totals['revenue'] - $totals['expense'];
        $totals['comparison_net_income'] = $totals['comparison_revenue'] - $totals['comparison_expense'];

        $this->appendChangeMetrics($totals, ['revenue', 'expense', 'net_income']);

        return [
            'revenues' => $this->buildTree($buckets['revenues']),
            'expenses' => $this->buildTree($buckets['expenses']),
            'totals' => $totals,
        ];
    }

    public function getComparativeReport(
        int $fiscalYearId,
        ?int $comparisonFiscalYearId = null,
        ?int $branchId = null,
    ): array {
        $coaVersion = $this->resolveRequiredCoaVersion($fiscalYearId);

        if (! $coaVersion) {
            return [
                'assets' => [],
                'liabilities' => [],
                'equity' => [],
                'revenues' => [],
                'expenses' => [],
                'totals' => [],
            ];
        }

        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId, $branchId)
            ->orderBy('code')
            ->get();

        ['comparisonBalanceByCurrentAccountId' => $comparisonBalanceByCurrentAccountId]
            = $this->prepareComparisonContext($accounts, $comparisonFiscalYearId, $branchId);

        ['buckets' => $buckets, 'totals' => $totals] = $this->collectAccountBuckets(
            $accounts,
            $comparisonFiscalYearId,
            $comparisonBalanceByCurrentAccountId,
            [
                'asset' => ['bucket' => 'assets', 'total' => 'assets'],
                'liability' => ['bucket' => 'liabilities', 'total' => 'liabilities'],
                'equity' => ['bucket' => 'equity', 'total' => 'equity'],
                'revenue' => ['bucket' => 'revenues', 'total' => 'revenues'],
                'expense' => ['bucket' => 'expenses', 'total' => 'expenses'],
            ]
        );

        // Laba tahun berjalan (pendapatan - beban) masuk ekuitas pada kedua
        // periode agar Kewajiban + Ekuitas = Aset di laporan perbandingan.
        $netIncome = $totals['revenues'] - $totals['expenses'];
        $comparisonNetIncome = $totals['comparison_revenues'] - $totals['comparison_expenses'];
        $this->appendCurrentYearEarnings($buckets, $totals, $netIncome, $comparisonNetIncome);

        $this->appendChangeMetrics($totals, ['assets', 'liabilities', 'equity', 'revenues', 'expenses']);

        return [
            'assets' => $this->buildTree($buckets['assets']),
            'liabilities' => $this->buildTree($buckets['liabilities']),
            'equity' => $this->buildTree($buckets['equity']),
            'revenues' => $this->buildTree($buckets['revenues']),
            'expenses' => $this->buildTree($buckets['expenses']),
            'totals' => $totals,
        ];
    }

    private function emptyBalanceSheetReport(): array
    {
        return [
            'assets' => [],
            'liabilities' => [],
            'equity' => [],
            'totals' => [
                'assets' => 0,
                'liabilities' => 0,
                'equity' => 0,
                'current_year_earnings' => 0,
                'comparison_assets' => 0,
                'comparison_liabilities' => 0,
                'comparison_equity' => 0,
                'change_assets' => 0,
                'change_liabilities' => 0,
                'change_equity' => 0,
                'change_percentage_assets' => 0,
                'change_percentage_liabilities' => 0,
                'change_percentage_equity' => 0,
            ],
        ];
    }

    private function emptyIncomeStatementReport(): array
    {
        return [
            'revenues' => [],
            'expenses' => [],
            'totals' => [
                'revenue' => 0,
                'expense' => 0,
                'net_income' => 0,
                'comparison_revenue' => 0,
                'comparison_expense' => 0,
                'comparison_net_income' => 0,
                'change_revenue' => 0,
                'change_expense' => 0,
                'change_net_income' => 0,
                'change_percentage_revenue' => 0,
                'change_percentage_expense' => 0,
                'change_percentage_net_income' => 0,
            ],
        ];
    }

    private function calculateNetIncome(int $fiscalYearId, int $coaVersionId, ?int $branchId = null): float
    {
        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums($coaVersionId, $fiscalYearId, $branchId)
            ->whereIn('type', ['revenue', 'expense'])
            ->get();

        $revenue = 0;
        $expense = 0;

        foreach ($accounts as $account) {
            $debit = $account->posted_debit_sum ?? 0;
            $credit = $account->posted_credit_sum ?? 0;

            if ($account->type === 'revenue') {
                // Revenue is Credit normal
                $revenue += ($credit - $debit);
            } else {
                // Expense is Debit normal
                $expense += ($debit - $credit);
            }
        }

        return $revenue - $expense;
    }

    private function resolveRequiredCoaVersion(int $fiscalYearId): ?CoaVersion
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);

        return $this->resolveCoaVersionForFiscalYear($fiscalYear);
    }

    private function resolveCoaVersionForFiscalYear(FiscalYear $fiscalYear): ?CoaVersion
    {
        /** @var CoaVersion|null $coaVersion */
        $coaVersion = $fiscalYear->coaVersions()
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'archived' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->first();

        return $coaVersion;
    }

    private function resolveComparisonCoaVersion(?int $comparisonFiscalYearId): ?CoaVersion
    {
        if (! $comparisonFiscalYearId) {
            return null;
        }

        /** @var FiscalYear|null $comparisonFiscalYear */
        $comparisonFiscalYear = FiscalYear::find($comparisonFiscalYearId);

        return $comparisonFiscalYear
            ? $this->resolveCoaVersionForFiscalYear($comparisonFiscalYear)
            : null;
    }

    private function resolveComparisonBalanceMap(
        Collection $accounts,
        ?int $comparisonFiscalYearId,
        ?CoaVersion $comparisonCoaVersion,
        ?int $branchId = null,
        array $departmentFilter = [],
    ): array {
        if (! $comparisonFiscalYearId || ! $comparisonCoaVersion) {
            return [];
        }

        return $this->buildComparisonBalanceMap(
            $accounts,
            $comparisonFiscalYearId,
            $comparisonCoaVersion,
            $branchId,
            $departmentFilter,
        );
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array{comparisonCoaVersion: ?CoaVersion, comparisonBalanceByCurrentAccountId: array<int, float|int>}
     */
    private function prepareComparisonContext(
        Collection $accounts,
        ?int $comparisonFiscalYearId,
        ?int $branchId = null,
        array $departmentFilter = [],
    ): array {
        $comparisonCoaVersion = $this->resolveComparisonCoaVersion($comparisonFiscalYearId);

        return [
            'comparisonCoaVersion' => $comparisonCoaVersion,
            'comparisonBalanceByCurrentAccountId' => $this->resolveComparisonBalanceMap(
                $accounts,
                $comparisonFiscalYearId,
                $comparisonCoaVersion,
                $branchId,
                $departmentFilter,
            ),
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  array<int, float>  $comparisonBalanceByCurrentAccountId
     * @return array{accounts: Collection<int, Account>, item: array<string, mixed>|null, bucket: string}
     */
    private function extractClearingReclassification(
        Collection $accounts,
        ?int $branchId,
        ?int $comparisonFiscalYearId,
        array $comparisonBalanceByCurrentAccountId,
    ): array {
        if ($branchId === null) {
            return ['accounts' => $accounts, 'item' => null, 'bucket' => 'assets'];
        }

        /** @var Account|null $clearing */
        $clearing = $accounts->firstWhere('code', InterBranchClearingService::CLEARING_CODE);

        if (! $clearing) {
            return ['accounts' => $accounts, 'item' => null, 'bucket' => 'assets'];
        }

        $remaining = $accounts->reject(
            fn (Account $account): bool => $account->code === InterBranchClearingService::CLEARING_CODE,
        )->values();

        $balance = $this->computeAccountBalance(
            $clearing->normal_balance,
            $clearing->posted_debit_sum ?? 0,
            $clearing->posted_credit_sum ?? 0,
        );
        $comparisonBalance = $comparisonFiscalYearId
            ? (float) ($comparisonBalanceByCurrentAccountId[$clearing->id] ?? 0)
            : 0.0;

        if (round($balance, 2) === 0.0 && round($comparisonBalance, 2) === 0.0) {
            return ['accounts' => $remaining, 'item' => null, 'bucket' => 'assets'];
        }

        $isDueFrom = $balance >= 0;

        $item = [
            'id' => $clearing->id,
            'code' => $clearing->code,
            'name' => $isDueFrom ? 'Due From Branches' : 'Due To Branches',
            'level' => 1,
            'parent_id' => null,
            'balance' => $isDueFrom ? $balance : -$balance,
            'comparison_balance' => $isDueFrom ? $comparisonBalance : -$comparisonBalance,
            'sub_type' => $isDueFrom ? 'current_asset' : 'current_liability',
        ];

        return [
            'accounts' => $remaining,
            'item' => $item,
            'bucket' => $isDueFrom ? 'assets' : 'liabilities',
        ];
    }

    private function buildReportItem(Account $account, float $balance, float $comparisonBalance): array
    {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'level' => $account->level,
            'parent_id' => $account->parent_id,
            'balance' => $balance,
            'comparison_balance' => $comparisonBalance,
            'sub_type' => $account->sub_type,
        ];
    }

    private function baseAccountPayload(Account $account): array
    {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
            'normal_balance' => $account->normal_balance,
            'level' => $account->level,
            'parent_id' => $account->parent_id,
        ];
    }

    private function mapAccountsWithNetMovement(Collection $accounts, callable $rowBuilder): array
    {
        return $accounts->map(function (Account $account) use ($rowBuilder) {
            $debit = (float) ($account->posted_debit_sum ?? 0);
            $credit = (float) ($account->posted_credit_sum ?? 0);

            ['debit' => $netDebit, 'credit' => $netCredit] = $this->splitNetByNormalBalance(
                $account->normal_balance,
                $debit,
                $credit
            );

            return $rowBuilder($account, $debit, $credit, $netDebit, $netCredit);
        })->values()->toArray();
    }

    /**
     * @param  array<string, array{bucket: string, total: string}>  $bucketMap
     * @return array{buckets: array<string, array<int, array<string, mixed>>>, totals: array<string, float>}
     */
    private function collectAccountBuckets(
        Collection $accounts,
        ?int $comparisonFiscalYearId,
        array $comparisonBalanceByCurrentAccountId,
        array $bucketMap
    ): array {
        $buckets = [];
        $totals = [];

        foreach ($bucketMap as $config) {
            $bucketKey = $config['bucket'];
            $totalKey = $config['total'];

            $buckets[$bucketKey] = [];
            $totals[$totalKey] = 0;
            $totals["comparison_{$totalKey}"] = 0;
        }

        foreach ($accounts as $account) {
            if (! isset($bucketMap[$account->type])) {
                continue;
            }

            $debit = $account->posted_debit_sum ?? 0;
            $credit = $account->posted_credit_sum ?? 0;
            // Present balances by account type so contra accounts (e.g. an
            // accumulated-depreciation asset with a credit normal balance) net
            // against their bucket instead of inflating it (W7-05).
            $balance = $this->computeAccountBalance($this->effectiveNormalBalance($account), $debit, $credit);
            $comparisonBalance = $comparisonFiscalYearId
                ? (float) ($comparisonBalanceByCurrentAccountId[$account->id] ?? 0)
                : 0.0;

            $bucketKey = $bucketMap[$account->type]['bucket'];
            $totalKey = $bucketMap[$account->type]['total'];

            $buckets[$bucketKey][] = $this->buildReportItem($account, $balance, $comparisonBalance);
            $totals[$totalKey] += $balance;
            $totals["comparison_{$totalKey}"] += $comparisonBalance;

            // Total per akun dan per sub_type untuk kartu ringkasan
            // (computed_sections); dipakai EvaluateReportSectionsAction.
            $totals["account_{$account->code}"] = ($totals["account_{$account->code}"] ?? 0) + $balance;
            if ($account->sub_type !== null) {
                $totals[$account->sub_type] = ($totals[$account->sub_type] ?? 0) + $balance;
                $subTypeKey = "{$account->type}_{$account->sub_type}";
                $totals[$subTypeKey] = ($totals[$subTypeKey] ?? 0) + $balance;
            }
        }

        return [
            'buckets' => $buckets,
            'totals' => $totals,
        ];
    }

    /**
     * @param  array<string, float|int>  $totals
     * @param  array<int, string>  $keys
     */
    private function appendChangeMetrics(array &$totals, array $keys): void
    {
        foreach ($keys as $key) {
            $comparisonKey = "comparison_{$key}";
            $comparisonValue = (float) ($totals[$comparisonKey] ?? 0);
            $changeValue = (float) ($totals[$key] ?? 0) - $comparisonValue;

            $totals["change_{$key}"] = $changeValue;
            $totals["change_percentage_{$key}"] = $comparisonValue != 0
                ? ($changeValue / abs($comparisonValue)) * 100
                : 0;
        }
    }

    private function effectiveNormalBalance(Account $account): string
    {
        return match ($account->type) {
            'asset', 'expense' => 'debit',
            'liability', 'equity', 'revenue' => 'credit',
            default => $account->normal_balance,
        };
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $buckets
     * @param  array<string, float|int>  $totals
     */
    private function appendCurrentYearEarnings(
        array &$buckets,
        array &$totals,
        float $netIncome,
        float $comparisonNetIncome,
    ): void {
        $buckets['equity'][] = [
            'id' => 'current_year_earnings',
            'code' => '9999-CYE',
            'name' => 'Current Year Earnings',
            'level' => 1,
            'parent_id' => null,
            'balance' => $netIncome,
            'comparison_balance' => $comparisonNetIncome,
            'sub_type' => 'equity',
        ];
        $totals['equity'] += $netIncome;
        $totals['comparison_equity'] += $comparisonNetIncome;
        $totals['current_year_earnings'] = $netIncome;
        $totals['comparison_current_year_earnings'] = $comparisonNetIncome;
    }

    private function computeAccountBalance(string $normalBalance, float|int $debit, float|int $credit): float
    {
        $debit = (float) $debit;
        $credit = (float) $credit;

        return $normalBalance === 'debit' ? ($debit - $credit) : ($credit - $debit);
    }

    private function applyNormalBalance(string $normalBalance, float $rawNet): float
    {
        return $normalBalance === 'debit' ? $rawNet : -$rawNet;
    }

    /**
     * Nilai mentah (debit - kredit) per akun, sebelum diterjemahkan ke sisi
     * normal akun tujuan. Dipakai agar saldo CoA arsip dapat disajikan dengan
     * tanda akun versi baru (mis. akumulasi penyusutan tetap negatif).
     *
     * @param  Collection<int, Account>  $accounts
     * @param  callable(Account): (int|string)  $keyResolver
     * @return array<int|string, float>
     */
    private function mapAccountRawNets(Collection $accounts, callable $keyResolver): array
    {
        $nets = [];

        foreach ($accounts as $account) {
            $nets[$keyResolver($account)] = (float) ($account->posted_debit_sum ?? 0)
                - (float) ($account->posted_credit_sum ?? 0);
        }

        return $nets;
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<int, float>
     */
    private function mapAccountRawNetsById(Collection $accounts): array
    {
        /** @var array<int, float> $nets */
        $nets = $this->mapAccountRawNets(
            $accounts,
            static fn (Account $account): int => $account->id,
        );

        return $nets;
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<string, float>
     */
    private function mapAccountRawNetsByCode(Collection $accounts): array
    {
        /** @var array<string, float> $nets */
        $nets = $this->mapAccountRawNets(
            $accounts,
            static fn (Account $account): string => $account->code,
        );

        return $nets;
    }

    private function buildComparisonBalanceMap(
        Collection $currentAccounts,
        int $comparisonFiscalYearId,
        CoaVersion $comparisonCoaVersion,
        ?int $branchId = null,
        array $departmentFilter = [],
    ): array {
        $currentAccounts = $currentAccounts->values();
        $currentIds = $currentAccounts->pluck('id')->all();
        $codes = $currentAccounts->pluck('code')->unique()->values()->all();

        /** @var Collection<int, Account> $comparisonAccounts */
        $comparisonAccounts = $this
            ->accountsWithPostedSums(
                $comparisonCoaVersion->id,
                $comparisonFiscalYearId,
                $branchId,
                $departmentFilter['departmentId'] ?? null,
                $departmentFilter['withoutDepartment'] ?? false,
            )
            ->whereIn('code', $codes)
            ->get();

        $comparisonRawNetByCode = $this->mapAccountRawNetsByCode($comparisonAccounts);

        /** @var Collection<int, Account> $currentAccountsById */
        $currentAccountsById = $currentAccounts->keyBy('id');
        $comparisonBalanceByCurrentId = array_fill_keys($currentIds, 0);
        foreach ($currentAccounts as $account) {
            $comparisonBalanceByCurrentId[$account->id] = $this->applyNormalBalance(
                $this->effectiveNormalBalance($account),
                (float) ($comparisonRawNetByCode[$account->code] ?? 0),
            );
        }

        $mappings = AccountMapping::query()
            ->whereIn('target_account_id', $currentIds)
            ->get(['source_account_id', 'target_account_id', 'type']);

        if ($mappings->isEmpty()) {
            return $comparisonBalanceByCurrentId;
        }

        $sourceIds = $mappings->pluck('source_account_id')->unique()->values()->all();
        /** @var Collection<int, Account> $sourceAccounts */
        $sourceAccounts = $this->accountsWithPostedSums(
            $comparisonCoaVersion->id,
            $comparisonFiscalYearId,
            $branchId,
            $departmentFilter['departmentId'] ?? null,
            $departmentFilter['withoutDepartment'] ?? false,
        )
            ->whereIn('id', $sourceIds)
            ->get();

        $sourceRawNetById = $this->mapAccountRawNetsById($sourceAccounts);

        $mappingRawNetSumByTargetId = [];
        foreach ($mappings as $mapping) {
            if ($mapping->type === 'split') {
                continue;
            }

            $mappingRawNetSumByTargetId[$mapping->target_account_id] =
                ($mappingRawNetSumByTargetId[$mapping->target_account_id] ?? 0)
                + ($sourceRawNetById[$mapping->source_account_id] ?? 0);
        }

        foreach ($mappingRawNetSumByTargetId as $targetId => $rawNetSum) {
            /** @var Account|null $target */
            $target = $currentAccountsById->get($targetId);
            if (! $target) {
                continue;
            }

            $comparisonBalanceByCurrentId[$targetId] = $this->applyNormalBalance(
                $this->effectiveNormalBalance($target),
                $rawNetSum,
            );
        }

        $splitGroups = $mappings->where('type', 'split')->groupBy('source_account_id');

        foreach ($splitGroups as $sourceId => $group) {
            $sourceRawNet = $sourceRawNetById[$sourceId] ?? 0;
            if (round($sourceRawNet, 2) === 0.0) {
                continue;
            }

            $targetIds = $group->pluck('target_account_id')->unique()->values()->all();
            if (count($targetIds) === 0) {
                continue;
            }

            // Pemetaan split tidak menyimpan bobot alokasi. Saldo sumber
            // ditempatkan pada target pertama (primary) agar subtotal induk
            // tetap sama dengan jumlah anak setelah pemetaan CoA lama -> baru.
            $primaryTargetId = (int) $targetIds[0];
            /** @var Account|null $target */
            $target = $currentAccountsById->get($primaryTargetId);
            if (! $target) {
                continue;
            }

            $comparisonBalanceByCurrentId[$primaryTargetId] =
                ($comparisonBalanceByCurrentId[$primaryTargetId] ?? 0)
                + $this->applyNormalBalance($this->effectiveNormalBalance($target), $sourceRawNet);
        }

        return $comparisonBalanceByCurrentId;
    }

    /**
     * Akun laba/rugi pelepasan aset (pengaturan, #442). Hasil penjualan aset masuk
     * investasi (PSAK 2), sehingga laba/rugi pelepasan tidak masuk operasi.
     *
     * @return list<int>
     */
    private function assetDisposalAccountIds(): array
    {
        $ids = [
            Setting::get(PostAssetDisposalJournalAction::GAIN_SETTING_KEY),
            Setting::get(PostAssetDisposalJournalAction::LOSS_SETTING_KEY),
        ];

        return array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            array_filter($ids, static fn (mixed $id): bool => $id !== null && $id !== ''),
        )));
    }

    private function classifyCashFlowActivity(?string $type, ?string $subType): string
    {
        if ($subType === 'non_current_asset') {
            return 'investing';
        }

        if ($subType === 'non_current_liability' || $type === 'equity') {
            return 'financing';
        }

        return 'operating';
    }

    private function accountsWithPostedSums(
        int $coaVersionId,
        int $fiscalYearId,
        ?int $branchId = null,
        ?int $departmentId = null,
        bool $withoutDepartment = false,
        bool $excludeOpeningEntries = false,
    ): Builder {
        // Alias columns to posted_*_sum (NOT total_debit/total_credit): the Account
        // model defines getTotalDebitAttribute/getTotalCreditAttribute accessors that
        // recompute unfiltered sums and would shadow any query column of that name.
        $postedSums = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYearId)
            ->where('journal_entries.status', 'posted')
            ->when($branchId !== null, fn ($q) => $q->where('journal_entry_lines.branch_id', $branchId))
            ->when($departmentId !== null, fn ($q) => $q->where('journal_entry_lines.department_id', $departmentId))
            ->when($withoutDepartment, fn ($q) => $q->whereNull('journal_entry_lines.department_id'))
            ->when($excludeOpeningEntries, fn ($q) => $this->excludeOpeningBalanceEntries($q))
            ->groupBy('journal_entry_lines.account_id')
            ->select('journal_entry_lines.account_id')
            ->selectRaw('SUM(journal_entry_lines.debit) as posted_debit_sum')
            ->selectRaw('SUM(journal_entry_lines.credit) as posted_credit_sum');

        return Account::query()
            ->where('accounts.coa_version_id', $coaVersionId)
            ->leftJoinSub($postedSums, 'posted_sums', 'posted_sums.account_id', '=', 'accounts.id')
            ->select('accounts.*', 'posted_sums.posted_debit_sum', 'posted_sums.posted_credit_sum');
    }

    /**
     * Jurnal saldo awal (referensi OPENING-FY-*) memindahkan saldo tahun lalu ke COA
     * aktif, sehingga bukan arus kas tahun berjalan. Kas awal dilaporkan terpisah
     * lewat getOpeningCashBalance() agar saldo kas tetap utuh.
     */
    private function excludeOpeningBalanceEntries(QueryBuilder|Builder $query): QueryBuilder|Builder
    {
        return $query->where(function ($nested): void {
            $nested->whereNull('journal_entries.reference')
                ->orWhere('journal_entries.reference', 'not like', 'OPENING-FY-%');
        });
    }

    /**
     * @return array{debit: float, credit: float}
     */
    private function splitNetByNormalBalance(string $normalBalance, float|int $debit, float|int $credit): array
    {
        $balance = $this->computeAccountBalance($normalBalance, $debit, $credit);

        if ($normalBalance === 'debit') {
            return $balance >= 0
                ? ['debit' => $balance, 'credit' => 0]
                : ['debit' => 0, 'credit' => abs($balance)];
        }

        return $balance >= 0
            ? ['debit' => 0, 'credit' => $balance]
            : ['debit' => abs($balance), 'credit' => 0];
    }

    private function buildTree(array $elements, $parentId = null): array
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                // We MUST pass $elements (the full flat list) to find children.
                $children = $this->buildTree($elements, $element['id']);

                // Initialize default if not set (for safety, though we set it in main loop)
                $element['balance'] = $element['balance'] ?? 0;
                $element['comparison_balance'] = $element['comparison_balance'] ?? 0;

                if ($children) {
                    $element['children'] = $children;

                    // Aggregate balances from children
                    $element['balance'] += collect($children)->sum('balance');
                    $element['comparison_balance'] += collect($children)->sum('comparison_balance');
                }

                // Calculate Variance (Change & Change %)
                // Change = Current - Comparison
                $element['change'] = $element['balance'] - $element['comparison_balance'];

                // Change % = (Change / Comparison) * 100
                if ($element['comparison_balance'] != 0) {
                    $element['change_percentage'] = ($element['change'] / abs($element['comparison_balance'])) * 100;
                } else {
                    $element['change_percentage'] = 0; // Or null to indicate N/A? Let's use 0 or handle in UI
                }

                $branch[] = $element;
            }
        }

        return $branch;
    }
}
