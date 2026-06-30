<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Builder;
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

    public function getCashFlow(int $fiscalYearId, ?int $branchId = null): array
    {
        $coaVersion = $this->resolveRequiredCoaVersion($fiscalYearId);

        if (! $coaVersion) {
            return [];
        }

        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId, $branchId)
            ->where('is_cash_flow', true)
            ->orderBy('code')
            ->get();

        return $this->mapAccountsWithNetMovement(
            $accounts,
            fn (Account $account, float $debit, float $credit, float $netDebit, float $netCredit) => [
                ...$this->baseAccountPayload($account),
                'inflow' => $netCredit,
                'outflow' => $netDebit,
            ]
        );
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
                $comparisonFiscalYearId, $comparisonCoaVersion->id, $branchId
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

        $this->appendChangeMetrics($totals, ['assets', 'liabilities', 'equity']);

        return [
            'assets' => $this->buildTree($buckets['assets']),
            'liabilities' => $this->buildTree($buckets['liabilities']),
            'equity' => $this->buildTree($buckets['equity']),
            'totals' => $totals,
        ];
    }

    public function getIncomeStatement(
        int $fiscalYearId,
        ?int $comparisonFiscalYearId = null,
        ?int $branchId = null,
    ): array {
        $coaVersion = $this->resolveRequiredCoaVersion($fiscalYearId);

        if (! $coaVersion) {
            return $this->emptyIncomeStatementReport();
        }

        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId, $branchId)
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('code')
            ->get();

        ['comparisonBalanceByCurrentAccountId' => $comparisonBalanceByCurrentAccountId]
            = $this->prepareComparisonContext($accounts, $comparisonFiscalYearId, $branchId);

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
        ?int $branchId = null
    ): array {
        if (! $comparisonFiscalYearId || ! $comparisonCoaVersion) {
            return [];
        }

        return $this->buildComparisonBalanceMap(
            $accounts,
            $comparisonFiscalYearId,
            $comparisonCoaVersion,
            $branchId
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
    ): array {
        $comparisonCoaVersion = $this->resolveComparisonCoaVersion($comparisonFiscalYearId);

        return [
            'comparisonCoaVersion' => $comparisonCoaVersion,
            'comparisonBalanceByCurrentAccountId' => $this->resolveComparisonBalanceMap(
                $accounts,
                $comparisonFiscalYearId,
                $comparisonCoaVersion,
                $branchId,
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
            $balance = $this->computeAccountBalance($account->normal_balance, $debit, $credit);
            $comparisonBalance = $comparisonFiscalYearId
                ? (float) ($comparisonBalanceByCurrentAccountId[$account->id] ?? 0)
                : 0.0;

            $bucketKey = $bucketMap[$account->type]['bucket'];
            $totalKey = $bucketMap[$account->type]['total'];

            $buckets[$bucketKey][] = $this->buildReportItem($account, $balance, $comparisonBalance);
            $totals[$totalKey] += $balance;
            $totals["comparison_{$totalKey}"] += $comparisonBalance;
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

    private function computeAccountBalance(string $normalBalance, float|int $debit, float|int $credit): float
    {
        $debit = (float) $debit;
        $credit = (float) $credit;

        return $normalBalance === 'debit' ? ($debit - $credit) : ($credit - $debit);
    }

    private function computePostedBalance(Account $account): float
    {
        return $this->computeAccountBalance(
            $account->normal_balance,
            $account->posted_debit_sum ?? 0,
            $account->posted_credit_sum ?? 0
        );
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  callable(Account): (int|string)  $keyResolver
     * @return array<int|string, float>
     */
    private function mapAccountBalances(Collection $accounts, callable $keyResolver): array
    {
        $balances = [];

        foreach ($accounts as $account) {
            $balances[$keyResolver($account)] = $this->computePostedBalance($account);
        }

        return $balances;
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<int, float>
     */
    private function mapAccountBalancesById(Collection $accounts): array
    {
        /** @var array<int, float> $balances */
        $balances = $this->mapAccountBalances(
            $accounts,
            static fn (Account $account): int => $account->id,
        );

        return $balances;
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<string, float>
     */
    private function mapAccountBalancesByCode(Collection $accounts): array
    {
        /** @var array<string, float> $balances */
        $balances = $this->mapAccountBalances(
            $accounts,
            static fn (Account $account): string => $account->code,
        );

        return $balances;
    }

    private function buildComparisonBalanceMap(
        Collection $currentAccounts,
        int $comparisonFiscalYearId,
        CoaVersion $comparisonCoaVersion,
        ?int $branchId = null
    ): array {
        $currentAccounts = $currentAccounts->values();
        $currentIds = $currentAccounts->pluck('id')->all();
        $codes = $currentAccounts->pluck('code')->unique()->values()->all();

        /** @var Collection<int, Account> $comparisonAccounts */
        $comparisonAccounts = $this
            ->accountsWithPostedSums($comparisonCoaVersion->id, $comparisonFiscalYearId, $branchId)
            ->whereIn('code', $codes)
            ->get();

        $comparisonBalanceByCode = $this->mapAccountBalancesByCode($comparisonAccounts);

        $comparisonBalanceByCurrentId = array_fill_keys($currentIds, 0);
        foreach ($currentAccounts as $account) {
            $comparisonBalanceByCurrentId[$account->id] = $comparisonBalanceByCode[$account->code] ?? 0;
        }

        $mappings = AccountMapping::query()
            ->whereIn('target_account_id', $currentIds)
            ->get(['source_account_id', 'target_account_id', 'type']);

        if ($mappings->isEmpty()) {
            return $comparisonBalanceByCurrentId;
        }

        $sourceIds = $mappings->pluck('source_account_id')->unique()->values()->all();
        /** @var Collection<int, Account> $sourceAccounts */
        $sourceAccounts = $this->accountsWithPostedSums($comparisonCoaVersion->id, $comparisonFiscalYearId, $branchId)
            ->whereIn('id', $sourceIds)
            ->get();

        $sourceBalanceById = $this->mapAccountBalancesById($sourceAccounts);

        $mappingSumByTargetId = [];
        foreach ($mappings as $mapping) {
            if ($mapping->type === 'split') {
                continue;
            }

            $mappingSumByTargetId[$mapping->target_account_id] =
                ($mappingSumByTargetId[$mapping->target_account_id] ?? 0)
                + ($sourceBalanceById[$mapping->source_account_id] ?? 0);
        }

        foreach ($mappingSumByTargetId as $targetId => $sum) {
            $comparisonBalanceByCurrentId[$targetId] = $sum;
        }

        $parentMap = $currentAccounts->pluck('parent_id', 'id')->toArray();
        $splitGroups = $mappings->where('type', 'split')->groupBy('source_account_id');

        foreach ($splitGroups as $sourceId => $group) {
            $sourceBalance = $sourceBalanceById[$sourceId] ?? 0;
            if ($sourceBalance == 0) {
                continue;
            }

            $targetIds = $group->pluck('target_account_id')->unique()->values()->all();
            if (count($targetIds) === 0) {
                continue;
            }

            $lcaTargetId = $this->lowestCommonAncestor($targetIds, $parentMap);
            if (! $lcaTargetId) {
                $lcaTargetId = $targetIds[0];
            }

            $comparisonBalanceByCurrentId[$lcaTargetId] =
                ($comparisonBalanceByCurrentId[$lcaTargetId] ?? 0)
                + $sourceBalance;
        }

        return $comparisonBalanceByCurrentId;
    }

    private function lowestCommonAncestor(array $nodeIds, array $parentMap): ?int
    {
        if (count($nodeIds) === 0) {
            return null;
        }

        $common = $this->ancestorChain((int) $nodeIds[0], $parentMap);
        for ($i = 1; $i < count($nodeIds); $i++) {
            $ancestors = array_flip($this->ancestorChain((int) $nodeIds[$i], $parentMap));
            $common = array_values(array_filter($common, fn ($id) => isset($ancestors[$id])));
            if (count($common) === 0) {
                return null;
            }
        }

        return (int) $common[0];
    }

    private function ancestorChain(int $nodeId, array $parentMap): array
    {
        $chain = [];
        $visited = [];

        while ($nodeId && ! isset($visited[$nodeId])) {
            $visited[$nodeId] = true;
            $chain[] = $nodeId;
            $nodeId = (int) ($parentMap[$nodeId] ?? 0);
        }

        return $chain;
    }

    private function accountsWithPostedSums(int $coaVersionId, int $fiscalYearId, ?int $branchId = null): Builder
    {
        // Alias columns to posted_*_sum (NOT total_debit/total_credit): the Account
        // model defines getTotalDebitAttribute/getTotalCreditAttribute accessors that
        // recompute unfiltered sums and would shadow any query column of that name.
        $postedSums = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.fiscal_year_id', $fiscalYearId)
            ->where('journal_entries.status', 'posted')
            ->when($branchId !== null, fn ($q) => $q->where('journal_entry_lines.branch_id', $branchId))
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
