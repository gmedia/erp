<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialReportService
{
    /**
     * Get Trial Balance Report
     */
    public function getTrialBalance(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        // Assuming there is an active COA version for this fiscal year,
        // OR we just get all accounts that have transactions in this FY.
        // Based on design doc, accounts are linked to coa_version,
        // which is linked to fiscal_year via coa_versions table.
        // However, a simpler approach for now is to find the active COA version for this fiscal year.

        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (! $coaVersion) {
            // Fallback: try to find any version or handle error
            // For now, let's assume one exists or return empty
            return [];
        }

        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId)
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

    public function getCashFlow(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (! $coaVersion) {
            return [];
        }

        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId)
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
     * Get Balance Sheet Report
     */
    public function getBalanceSheet(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (! $coaVersion) {
            return [];
        }

        // 1. Get Asset, Liability, Equity Accounts
        // We need to fetch balances for BOTH years.
        // Complex part: COA might be different.
        // For this task, we assume we display the CURRENT COA structure,
        // and map previous years data to it IF possible (via mapping or just same code).
        // Design doc says: "Sistem akan mencoba mencocokkan akun berdasarkan kolom code."

        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->orderBy('code')
            ->get();

        [
            'comparisonCoaVersion' => $comparisonCoaVersion,
            'comparisonBalanceByCurrentAccountId' => $comparisonBalanceByCurrentAccountId,
        ] = $this->prepareComparisonContext($accounts, $comparisonFiscalYearId);

        // 2. Calculate Net Income for Current Year
        $netIncome = $this->calculateNetIncome($fiscalYearId, $coaVersion->id);

        // 3. Calculate Net Income for Comparison Year (if exists)
        $comparisonNetIncome = 0;
        if ($comparisonFiscalYearId && $comparisonCoaVersion) {
            $comparisonNetIncome = $this->calculateNetIncome($comparisonFiscalYearId, $comparisonCoaVersion->id);
        }

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

    public function getIncomeStatement(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (! $coaVersion) {
            return [];
        }

        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId)
            ->whereIn('type', ['revenue', 'expense'])
            ->orderBy('code')
            ->get();

        ['comparisonBalanceByCurrentAccountId' => $comparisonBalanceByCurrentAccountId] = $this->prepareComparisonContext(
            $accounts,
            $comparisonFiscalYearId,
        );

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

    public function getComparativeReport(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

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
        $accounts = $this->accountsWithPostedSums($coaVersion->id, $fiscalYearId)
            ->orderBy('code')
            ->get();

        ['comparisonBalanceByCurrentAccountId' => $comparisonBalanceByCurrentAccountId] = $this->prepareComparisonContext(
            $accounts,
            $comparisonFiscalYearId,
        );

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

    private function calculateNetIncome(int $fiscalYearId, int $coaVersionId): float
    {
        /** @var Collection<int, Account> $accounts */
        $accounts = $this->accountsWithPostedSums($coaVersionId, $fiscalYearId)
            ->whereIn('type', ['revenue', 'expense'])
            ->get();

        $revenue = 0;
        $expense = 0;

        foreach ($accounts as $account) {
            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;

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
        ?CoaVersion $comparisonCoaVersion
    ): array {
        if (! $comparisonFiscalYearId || ! $comparisonCoaVersion) {
            return [];
        }

        return $this->buildComparisonBalanceMap(
            $accounts,
            $comparisonFiscalYearId,
            $comparisonCoaVersion
        );
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array{comparisonCoaVersion: ?CoaVersion, comparisonBalanceByCurrentAccountId: array<int, float|int>}
     */
    private function prepareComparisonContext(Collection $accounts, ?int $comparisonFiscalYearId): array
    {
        $comparisonCoaVersion = $this->resolveComparisonCoaVersion($comparisonFiscalYearId);

        return [
            'comparisonCoaVersion' => $comparisonCoaVersion,
            'comparisonBalanceByCurrentAccountId' => $this->resolveComparisonBalanceMap(
                $accounts,
                $comparisonFiscalYearId,
                $comparisonCoaVersion,
            ),
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
            $debit = (float) ($account->total_debit ?? 0);
            $credit = (float) ($account->total_credit ?? 0);

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

            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;
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
            $account->total_debit ?? 0,
            $account->total_credit ?? 0
        );
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<int, float>
     */
    private function mapAccountBalancesById(Collection $accounts): array
    {
        $balanceById = [];

        foreach ($accounts as $account) {
            $balanceById[$account->id] = $this->computePostedBalance($account);
        }

        return $balanceById;
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<string, float>
     */
    private function mapAccountBalancesByCode(Collection $accounts): array
    {
        $balanceByCode = [];

        foreach ($accounts as $account) {
            $balanceByCode[$account->code] = $this->computePostedBalance($account);
        }

        return $balanceByCode;
    }

    private function buildComparisonBalanceMap(
        Collection $currentAccounts,
        int $comparisonFiscalYearId,
        CoaVersion $comparisonCoaVersion
    ): array {
        $currentAccounts = $currentAccounts->values();
        $currentIds = $currentAccounts->pluck('id')->all();
        $codes = $currentAccounts->pluck('code')->unique()->values()->all();

        /** @var Collection<int, Account> $comparisonAccounts */
        $comparisonAccounts = $this->accountsWithPostedSums($comparisonCoaVersion->id, $comparisonFiscalYearId)
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
        $sourceAccounts = $this->accountsWithPostedSums($comparisonCoaVersion->id, $comparisonFiscalYearId)
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

    private function accountsWithPostedSums(int $coaVersionId, int $fiscalYearId): Builder
    {
        return Account::where('coa_version_id', $coaVersionId)
            ->withSum(['journalLines as total_debit' => function ($query) use ($fiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)
                        ->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalLines as total_credit' => function ($query) use ($fiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)
                        ->where('status', 'posted');
                });
            }], 'credit');
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
