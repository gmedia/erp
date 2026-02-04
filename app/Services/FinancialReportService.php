<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        // Based on design doc, accounts are linked to coa_version, which is linked to fiscal_year via coa_versions table.
        // However, a simpler approach for now is to find the active COA version for this fiscal year.
        
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);
        
        if (!$coaVersion) {
             // Fallback: try to find any version or handle error
             // For now, let's assume one exists or return empty
             return [];
        }

        $accounts = Account::where('coa_version_id', $coaVersion->id)
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
            }], 'credit')
            ->orderBy('code')
            ->get();

        $report = $accounts->map(function ($account) {
            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;
            
            // For Trial Balance, we usually show net Debit or net Credit based on normal balance ??
            // OR we show both Total Debit and Total Credit columns. 
            // Often Trial Balance shows Ending Debit OR Ending Credit.
            
            $netDebit = 0;
            $netCredit = 0;

            if ($account->normal_balance === 'debit') {
                $balance = $debit - $credit;
                if ($balance >= 0) {
                    $netDebit = $balance;
                } else {
                    $netCredit = abs($balance);
                }
            } else {
                $balance = $credit - $debit;
                 if ($balance >= 0) {
                    $netCredit = $balance;
                } else {
                    $netDebit = abs($balance);
                }
            }

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
                'level' => $account->level,
                'parent_id' => $account->parent_id,
                'debit' => $netDebit, // Net Movement
                'credit' => $netCredit, // Net Movement
                'raw_debit' => $debit, // Total Debits in period
                'raw_credit' => $credit, // Total Credits in period
            ];
        })->values(); // Remove filter to show all accounts

        return $report->toArray();
    }

    public function getCashFlow(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (!$coaVersion) {
            return [];
        }

        $accounts = Account::where('coa_version_id', $coaVersion->id)
            ->where('is_cash_flow', true)
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
            }], 'credit')
            ->orderBy('code')
            ->get();

        $report = $accounts->map(function ($account) {
            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;

            $netDebit = 0;
            $netCredit = 0;

            if ($account->normal_balance === 'debit') {
                $balance = $debit - $credit;
                if ($balance >= 0) {
                    $netDebit = $balance;
                } else {
                    $netCredit = abs($balance);
                }
            } else {
                $balance = $credit - $debit;
                if ($balance >= 0) {
                    $netCredit = $balance;
                } else {
                    $netDebit = abs($balance);
                }
            }

            return [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'normal_balance' => $account->normal_balance,
                'level' => $account->level,
                'parent_id' => $account->parent_id,
                'inflow' => $netCredit,
                'outflow' => $netDebit,
            ];
        })->values();

        return $report->toArray();
    }

    /**
     * Get Balance Sheet Report
     */
    public function getBalanceSheet(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);
        
        if (!$coaVersion) return [];

        // 1. Calculate Net Income for Current Year
        $netIncome = $this->calculateNetIncome($fiscalYearId, $coaVersion->id);
        
        // 2. Calculate Net Income for Comparison Year (if exists)
        $comparisonNetIncome = 0;
        $comparisonCoaVersion = null;
        if ($comparisonFiscalYearId) {
             $comparisonFiscalYear = FiscalYear::find($comparisonFiscalYearId);
             // Ideally we should find the COA version used in that year. 
             // Simplification: Assume logic allows finding it.
             $comparisonCoaVersion = $comparisonFiscalYear ? $this->resolveCoaVersionForFiscalYear($comparisonFiscalYear) : null;
             if ($comparisonCoaVersion) {
                 $comparisonNetIncome = $this->calculateNetIncome($comparisonFiscalYearId, $comparisonCoaVersion->id);
             }
        }

        // 3. Get Asset, Liability, Equity Accounts
        // We need to fetch balances for BOTH years.
        // Complex part: COA might be different. 
        // For this task, we assume we display the CURRENT COA structure, 
        // and map previous years data to it IF possible (via mapping or just same code).
        // Design doc says: "Sistem akan mencoba mencocokkan akun berdasarkan kolom code."
        
        $accounts = Account::where('coa_version_id', $coaVersion->id)
            ->whereIn('type', ['asset', 'liability', 'equity'])
            ->withSum(['journalLines as total_debit' => function ($query) use ($fiscalYearId) {
                 $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'debit')
             ->withSum(['journalLines as total_credit' => function ($query) use ($fiscalYearId) {
                 $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'credit')
            ->orderBy('code')
            ->get();

        // If comparison needed, we might need a separate query to get comparison balances 
        // because we can't easily sum relations with different filters in one go (unless we use different aliases).
        // Let's use separate aliases if possible, OR just separate query.
        // Eloquent `withSum` uses subqueries, so we can add more `withSum` for comparison.
        
        $comparisonBalanceByCurrentAccountId = [];
        if ($comparisonFiscalYearId && $comparisonCoaVersion) {
            $comparisonBalanceByCurrentAccountId = $this->buildComparisonBalanceMap($accounts, $comparisonFiscalYearId, $comparisonCoaVersion);
        }

        $assets = [];
        $liabilities = [];
        $equity = [];
        
        // Track totals
        $totals = [
            'assets' => 0,
            'liabilities' => 0,
            'equity' => 0,
            'comparison_assets' => 0,
            'comparison_liabilities' => 0,
            'comparison_equity' => 0,
        ];

        foreach ($accounts as $account) {
            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;
            
            $balance = ($account->normal_balance === 'debit') ? ($debit - $credit) : ($credit - $debit);
            
            // Get Comparison Balance
            $compBalance = 0;
            if ($comparisonFiscalYearId) {
                $compBalance = $comparisonBalanceByCurrentAccountId[$account->id] ?? 0;
            }

            $item = [
                 'id' => $account->id,
                 'code' => $account->code,
                 'name' => $account->name,
                 'level' => $account->level,
                 'parent_id' => $account->parent_id,
                 'balance' => $balance,
                 'comparison_balance' => $compBalance,
                 'sub_type' => $account->sub_type
            ];

            if ($account->type === 'asset') {
                $assets[] = $item;
                $totals['assets'] += $balance;
                $totals['comparison_assets'] += $compBalance;
            } elseif ($account->type === 'liability') {
                $liabilities[] = $item;
                $totals['liabilities'] += $balance;
                $totals['comparison_liabilities'] += $compBalance;
            } elseif ($account->type === 'equity') {
                $equity[] = $item;
                $totals['equity'] += $balance;
                $totals['comparison_equity'] += $compBalance;
            }
        }

        // Add Current Year Earnings to Equity
        $equity[] = [
            'id' => 'current_year_earnings',
            'code' => '9999-CYE',
            'name' => 'Current Year Earnings',
            'level' => 1,
            'parent_id' => null,
            'balance' => $netIncome,
            'comparison_balance' => $comparisonNetIncome,
            'sub_type' => 'equity'
        ];
        $totals['equity'] += $netIncome;
        $totals['comparison_equity'] += $comparisonNetIncome;

        $totals['change_assets'] = $totals['assets'] - $totals['comparison_assets'];
        $totals['change_percentage_assets'] = $totals['comparison_assets'] != 0 ? ($totals['change_assets'] / abs($totals['comparison_assets'])) * 100 : 0;

        $totals['change_liabilities'] = $totals['liabilities'] - $totals['comparison_liabilities'];
        $totals['change_percentage_liabilities'] = $totals['comparison_liabilities'] != 0 ? ($totals['change_liabilities'] / abs($totals['comparison_liabilities'])) * 100 : 0;

        $totals['change_equity'] = $totals['equity'] - $totals['comparison_equity'];
        $totals['change_percentage_equity'] = $totals['comparison_equity'] != 0 ? ($totals['change_equity'] / abs($totals['comparison_equity'])) * 100 : 0;

        return [
            'assets' => $this->buildTree($assets),
            'liabilities' => $this->buildTree($liabilities),
            'equity' => $this->buildTree($equity),
            'totals' => $totals
        ];
    }

    public function getIncomeStatement(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (!$coaVersion) return [];

        $comparisonCoaVersion = null;
        if ($comparisonFiscalYearId) {
            $comparisonFiscalYear = FiscalYear::find($comparisonFiscalYearId);
            $comparisonCoaVersion = $comparisonFiscalYear ? $this->resolveCoaVersionForFiscalYear($comparisonFiscalYear) : null;
        }

        $accounts = Account::where('coa_version_id', $coaVersion->id)
            ->whereIn('type', ['revenue', 'expense'])
            ->withSum(['journalLines as total_debit' => function ($query) use ($fiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalLines as total_credit' => function ($query) use ($fiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'credit')
            ->orderBy('code')
            ->get();

        $comparisonBalanceByCurrentAccountId = [];
        if ($comparisonFiscalYearId && $comparisonCoaVersion) {
            $comparisonBalanceByCurrentAccountId = $this->buildComparisonBalanceMap($accounts, $comparisonFiscalYearId, $comparisonCoaVersion);
        }

        $revenues = [];
        $expenses = [];

        $totals = [
            'revenue' => 0,
            'expense' => 0,
            'net_income' => 0,
            'comparison_revenue' => 0,
            'comparison_expense' => 0,
            'comparison_net_income' => 0,
        ];

        foreach ($accounts as $account) {
            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;

            $balance = ($account->normal_balance === 'debit') ? ($debit - $credit) : ($credit - $debit);

            $compBalance = 0;
            if ($comparisonFiscalYearId) {
                $compBalance = $comparisonBalanceByCurrentAccountId[$account->id] ?? 0;
            }

            $item = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'level' => $account->level,
                'parent_id' => $account->parent_id,
                'balance' => $balance,
                'comparison_balance' => $compBalance,
                'sub_type' => $account->sub_type,
            ];

            if ($account->type === 'revenue') {
                $revenues[] = $item;
                $totals['revenue'] += $balance;
                $totals['comparison_revenue'] += $compBalance;
            } else {
                $expenses[] = $item;
                $totals['expense'] += $balance;
                $totals['comparison_expense'] += $compBalance;
            }
        }

        $totals['net_income'] = $totals['revenue'] - $totals['expense'];
        $totals['comparison_net_income'] = $totals['comparison_revenue'] - $totals['comparison_expense'];

        $totals['change_revenue'] = $totals['revenue'] - $totals['comparison_revenue'];
        $totals['change_percentage_revenue'] = $totals['comparison_revenue'] != 0 ? ($totals['change_revenue'] / abs($totals['comparison_revenue'])) * 100 : 0;

        $totals['change_expense'] = $totals['expense'] - $totals['comparison_expense'];
        $totals['change_percentage_expense'] = $totals['comparison_expense'] != 0 ? ($totals['change_expense'] / abs($totals['comparison_expense'])) * 100 : 0;

        $totals['change_net_income'] = $totals['net_income'] - $totals['comparison_net_income'];
        $totals['change_percentage_net_income'] = $totals['comparison_net_income'] != 0 ? ($totals['change_net_income'] / abs($totals['comparison_net_income'])) * 100 : 0;

        return [
            'revenues' => $this->buildTree($revenues),
            'expenses' => $this->buildTree($expenses),
            'totals' => $totals,
        ];
    }

    public function getComparativeReport(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $this->resolveCoaVersionForFiscalYear($fiscalYear);

        if (!$coaVersion) {
            return [
                'assets' => [],
                'liabilities' => [],
                'equity' => [],
                'revenues' => [],
                'expenses' => [],
                'totals' => [],
            ];
        }

        $accounts = Account::where('coa_version_id', $coaVersion->id)
            ->withSum(['journalLines as total_debit' => function ($query) use ($fiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalLines as total_credit' => function ($query) use ($fiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'credit')
            ->orderBy('code')
            ->get();

        $comparisonCoaVersion = null;
        if ($comparisonFiscalYearId) {
            $comparisonFiscalYear = FiscalYear::find($comparisonFiscalYearId);
            $comparisonCoaVersion = $comparisonFiscalYear ? $this->resolveCoaVersionForFiscalYear($comparisonFiscalYear) : null;
        }

        $comparisonBalanceByCurrentAccountId = [];
        if ($comparisonFiscalYearId && $comparisonCoaVersion) {
            $comparisonBalanceByCurrentAccountId = $this->buildComparisonBalanceMap($accounts, $comparisonFiscalYearId, $comparisonCoaVersion);
        }

        $assets = [];
        $liabilities = [];
        $equity = [];
        $revenues = [];
        $expenses = [];

        $totals = [
            'assets' => 0,
            'liabilities' => 0,
            'equity' => 0,
            'revenues' => 0,
            'expenses' => 0,
            'comparison_assets' => 0,
            'comparison_liabilities' => 0,
            'comparison_equity' => 0,
            'comparison_revenues' => 0,
            'comparison_expenses' => 0,
        ];

        foreach ($accounts as $account) {
            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;
            $balance = $this->computeAccountBalance($account->normal_balance, $debit, $credit);

            $compBalance = 0;
            if ($comparisonFiscalYearId) {
                $compBalance = $comparisonBalanceByCurrentAccountId[$account->id] ?? 0;
            }

            $item = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'level' => $account->level,
                'parent_id' => $account->parent_id,
                'balance' => $balance,
                'comparison_balance' => $compBalance,
                'sub_type' => $account->sub_type,
            ];

            if ($account->type === 'asset') {
                $assets[] = $item;
                $totals['assets'] += $balance;
                $totals['comparison_assets'] += $compBalance;
            } elseif ($account->type === 'liability') {
                $liabilities[] = $item;
                $totals['liabilities'] += $balance;
                $totals['comparison_liabilities'] += $compBalance;
            } elseif ($account->type === 'equity') {
                $equity[] = $item;
                $totals['equity'] += $balance;
                $totals['comparison_equity'] += $compBalance;
            } elseif ($account->type === 'revenue') {
                $revenues[] = $item;
                $totals['revenues'] += $balance;
                $totals['comparison_revenues'] += $compBalance;
            } elseif ($account->type === 'expense') {
                $expenses[] = $item;
                $totals['expenses'] += $balance;
                $totals['comparison_expenses'] += $compBalance;
            }
        }

        foreach (['assets', 'liabilities', 'equity', 'revenues', 'expenses'] as $key) {
            $comparisonKey = "comparison_{$key}";
            $totals["change_{$key}"] = $totals[$key] - ($totals[$comparisonKey] ?? 0);
            $totals["change_percentage_{$key}"] = ($totals[$comparisonKey] ?? 0) != 0
                ? ($totals["change_{$key}"] / abs($totals[$comparisonKey])) * 100
                : 0;
        }

        return [
            'assets' => $this->buildTree($assets),
            'liabilities' => $this->buildTree($liabilities),
            'equity' => $this->buildTree($equity),
            'revenues' => $this->buildTree($revenues),
            'expenses' => $this->buildTree($expenses),
            'totals' => $totals,
        ];
    }

    private function calculateNetIncome(int $fiscalYearId, int $coaVersionId): float
    {
        $accounts = Account::where('coa_version_id', $coaVersionId)
            ->whereIn('type', ['revenue', 'expense'])
            ->withSum(['journalLines as total_debit' => function ($query) use ($fiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalLines as total_credit' => function ($query) use ($fiscalYearId) {
                 $query->whereHas('journalEntry', function ($q) use ($fiscalYearId) {
                    $q->where('fiscal_year_id', $fiscalYearId)->where('status', 'posted');
                });
            }], 'credit')
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
        return $fiscalYear->coaVersions()
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'archived' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->first();
    }

    private function computeAccountBalance(string $normalBalance, float|int $debit, float|int $credit): float
    {
        $debit = (float) $debit;
        $credit = (float) $credit;

        return $normalBalance === 'debit' ? ($debit - $credit) : ($credit - $debit);
    }

    private function buildComparisonBalanceMap(Collection $currentAccounts, int $comparisonFiscalYearId, CoaVersion $comparisonCoaVersion): array
    {
        $currentAccounts = $currentAccounts->values();
        $currentIds = $currentAccounts->pluck('id')->all();
        $codes = $currentAccounts->pluck('code')->unique()->values()->all();

        $comparisonByCode = Account::where('coa_version_id', $comparisonCoaVersion->id)
            ->whereIn('code', $codes)
            ->withSum(['journalLines as total_debit' => function ($query) use ($comparisonFiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($comparisonFiscalYearId) {
                    $q->where('fiscal_year_id', $comparisonFiscalYearId)->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalLines as total_credit' => function ($query) use ($comparisonFiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($comparisonFiscalYearId) {
                    $q->where('fiscal_year_id', $comparisonFiscalYearId)->where('status', 'posted');
                });
            }], 'credit')
            ->get()
            ->keyBy('code');

        $comparisonBalanceByCurrentId = [];
        foreach ($currentAccounts as $account) {
            $comparisonBalanceByCurrentId[$account->id] = 0;
            if (isset($comparisonByCode[$account->code])) {
                $compAcc = $comparisonByCode[$account->code];
                $comparisonBalanceByCurrentId[$account->id] = $this->computeAccountBalance(
                    $compAcc->normal_balance,
                    $compAcc->total_debit ?? 0,
                    $compAcc->total_credit ?? 0
                );
            }
        }

        $mappings = AccountMapping::query()
            ->whereIn('target_account_id', $currentIds)
            ->get(['source_account_id', 'target_account_id', 'type']);

        if ($mappings->isEmpty()) {
            return $comparisonBalanceByCurrentId;
        }

        $sourceIds = $mappings->pluck('source_account_id')->unique()->values()->all();
        $sourceAccounts = Account::where('coa_version_id', $comparisonCoaVersion->id)
            ->whereIn('id', $sourceIds)
            ->withSum(['journalLines as total_debit' => function ($query) use ($comparisonFiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($comparisonFiscalYearId) {
                    $q->where('fiscal_year_id', $comparisonFiscalYearId)->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalLines as total_credit' => function ($query) use ($comparisonFiscalYearId) {
                $query->whereHas('journalEntry', function ($q) use ($comparisonFiscalYearId) {
                    $q->where('fiscal_year_id', $comparisonFiscalYearId)->where('status', 'posted');
                });
            }], 'credit')
            ->get();

        $sourceBalanceById = [];
        foreach ($sourceAccounts as $sourceAccount) {
            $sourceBalanceById[$sourceAccount->id] = $this->computeAccountBalance(
                $sourceAccount->normal_balance,
                $sourceAccount->total_debit ?? 0,
                $sourceAccount->total_credit ?? 0
            );
        }

        $mappingSumByTargetId = [];
        foreach ($mappings as $mapping) {
            if ($mapping->type === 'split') {
                continue;
            }

            $mappingSumByTargetId[$mapping->target_account_id] = ($mappingSumByTargetId[$mapping->target_account_id] ?? 0)
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
            if (!$lcaTargetId) {
                $lcaTargetId = $targetIds[0];
            }

            $comparisonBalanceByCurrentId[$lcaTargetId] = ($comparisonBalanceByCurrentId[$lcaTargetId] ?? 0) + $sourceBalance;
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

        while ($nodeId && !isset($visited[$nodeId])) {
            $visited[$nodeId] = true;
            $chain[] = $nodeId;
            $nodeId = (int) ($parentMap[$nodeId] ?? 0);
        }

        return $chain;
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
