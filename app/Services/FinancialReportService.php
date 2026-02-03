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

    /**
     * Get Balance Sheet Report
     */
    public function getBalanceSheet(int $fiscalYearId, ?int $comparisonFiscalYearId = null): array
    {
        $fiscalYear = FiscalYear::findOrFail($fiscalYearId);
        $coaVersion = $fiscalYear->coaVersions()->where('status', 'active')->first();
        
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
             $comparisonCoaVersion = $comparisonFiscalYear?->coaVersions()->where('status', 'active')->first();
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
        
        $comparisonBalances = [];
        if ($comparisonFiscalYearId && $comparisonCoaVersion) {
            // We need to get balances of accounts in the COMPARISON COA, 
            // then map them to CURRENT COA codes.
            $comparisonAccounts = Account::where('coa_version_id', $comparisonCoaVersion->id)
                ->whereIn('type', ['asset', 'liability', 'equity'])
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
                ->keyBy('code'); // Map by code for easy lookup
                
             $comparisonBalances = $comparisonAccounts;
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
                // Find account with same code in comparison year
                if (isset($comparisonBalances[$account->code])) {
                    $compAcc = $comparisonBalances[$account->code];
                    $compDebit = $compAcc->total_debit ?? 0;
                    $compCredit = $compAcc->total_credit ?? 0;
                    // Assume normal balance is same as current account (safe assumption for same code)
                    $compBalance = ($account->normal_balance === 'debit') ? ($compDebit - $compCredit) : ($compCredit - $compDebit);
                }
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
