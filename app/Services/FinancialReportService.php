<?php

namespace App\Services;

use App\Models\Account;
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
        
        $coaVersion = $fiscalYear->coaVersions()->where('status', 'active')->first();
        
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
                'debit' => $netDebit, // Net Movement
                'credit' => $netCredit, // Net Movement
                'raw_debit' => $debit, // Total Debits in period
                'raw_credit' => $credit, // Total Credits in period
            ];
        })->filter(function($item) {
            // Optional: Filter out zero balance accounts? 
            // Usually we keep them to show the COA structure, but let's filter if both are 0
            return $item['debit'] != 0 || $item['credit'] != 0 || $item['raw_debit'] != 0 || $item['raw_credit'] != 0;
        })->values();

        return $report->toArray();
    }

    /**
     * Get Balance Sheet Report
     */
    public function getBalanceSheet(int $fiscalYearId): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $fiscalYear->coaVersions()->where('status', 'active')->first();
        
        if (!$coaVersion) return [];

        // 1. Calculate Net Income (Revenue - Expenses) for Current Year Earnings
        $netIncome = $this->calculateNetIncome($fiscalYearId, $coaVersion->id);

        // 2. Get Asset, Liability, Equity Accounts
        $accounts = Account::where('coa_version_id', $coaVersion->id)
            ->whereIn('type', ['asset', 'liability', 'equity'])
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

        $assets = [];
        $liabilities = [];
        $equity = [];

        foreach ($accounts as $account) {
            $debit = $account->total_debit ?? 0;
            $credit = $account->total_credit ?? 0;
            
            $balance = 0;
            if ($account->normal_balance === 'debit') {
                $balance = $debit - $credit;
            } else {
                $balance = $credit - $debit;
            }

            // Exclude zero balance accounts if desired, or keep them. keeping them for now.
            // if ($balance == 0) continue;

            $item = [
                 'id' => $account->id,
                 'code' => $account->code,
                 'name' => $account->name,
                 'level' => $account->level,
                 'parent_id' => $account->parent_id,
                 'balance' => $balance,
                 'sub_type' => $account->sub_type
            ];

            if ($account->type === 'asset') {
                $assets[] = $item;
            } elseif ($account->type === 'liability') {
                $liabilities[] = $item;
            } elseif ($account->type === 'equity') {
                $equity[] = $item;
            }
        }

        // Add Current Year Earnings to Equity
        if ($netIncome != 0) {
            $equity[] = [
                'id' => 'current_year_earnings',
                'code' => '9999-CYE', // Temporary code
                'name' => 'Current Year Earnings',
                'level' => 1,
                'parent_id' => null, // Top level
                'balance' => $netIncome,
                'sub_type' => 'equity'
            ];
        }

        return [
            'assets' => $this->buildTree($assets),
            'liabilities' => $this->buildTree($liabilities),
            'equity' => $this->buildTree($equity),
            'totals' => [
                'assets' => collect($assets)->sum('balance'),
                'liabilities' => collect($liabilities)->sum('balance'),
                'equity' => collect($equity)->sum('balance'),
            ]
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

    private function buildTree(array $elements, $parentId = null): array
    {
        $branch = [];

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                    // Aggregate balance from children if this is a header account
                    // But assume 'balance' already has posted values. 
                    // If header accounts don't have direct entries, we might need to sum children.
                    // For typical COA, direct posting to headers is disabled, they just aggregate.
                    // BUT, current query gets balance from JournalEntryLines directly linked to this account.
                    // If this is a header account, it likely has 0 balance in lines. 
                    // So we should sum children balance to this node.
                    $element['balance'] += collect($children)->sum('balance');
                }
                $branch[] = $element;
            }
        }

        // What if there are elements with parent_ids that are not in the list?
        // (e.g. parent is not asset/liability/equity? Unlikely)
        // Or if filter excluded the parent?
        // For now, assume tree is clean.

        return $branch;
    }
}
