<?php

namespace App\Actions\FinancialDashboard;

use App\Services\FinancialReportService;

class GetFinancialDashboardDataAction
{
    public function __construct(
        protected FinancialReportService $reportService,
    ) {}

    public function execute(int $fiscalYearId, ?int $comparisonYearId = null, ?int $branchId = null): array
    {
        // Segment reporting (Option 3): branch is a P&L dimension only.
        // Income statement + monthly trends are branch-scoped; balance sheet and
        // cash flow stay company-wide (cash is centrally pooled at null-branch).
        $balanceSheet = $this->reportService->getBalanceSheet($fiscalYearId, $comparisonYearId);
        $incomeStatement = $this->reportService->getIncomeStatement($fiscalYearId, $comparisonYearId, $branchId);
        $cashFlow = $this->reportService->getCashFlow($fiscalYearId);

        // Extract KPIs from totals
        $bsTotals = $balanceSheet['totals'] ?? [];
        $isTotals = $incomeStatement['totals'] ?? [];

        // Cash balance = sum inflow - sum outflow from cash flow accounts
        $cashInflow = collect($cashFlow)->sum('inflow');
        $cashOutflow = collect($cashFlow)->sum('outflow');
        $cashBalance = $cashInflow - $cashOutflow;

        $isBranchScoped = $branchId !== null;
        $segmentScope = $isBranchScoped ? 'branch' : 'company';

        return [
            'kpis' => [
                'revenue' => [
                    'value' => $isTotals['revenue'] ?? 0,
                    'change' => $isTotals['change_percentage_revenue'] ?? 0,
                    'comparison_value' => $isTotals['comparison_revenue'] ?? 0,
                    'scope' => $segmentScope,
                ],
                'expenses' => [
                    'value' => $isTotals['expense'] ?? 0,
                    'change' => $isTotals['change_percentage_expense'] ?? 0,
                    'comparison_value' => $isTotals['comparison_expense'] ?? 0,
                    'scope' => $segmentScope,
                ],
                'net_income' => [
                    'value' => $isTotals['net_income'] ?? 0,
                    'change' => $isTotals['change_percentage_net_income'] ?? 0,
                    'comparison_value' => $isTotals['comparison_net_income'] ?? 0,
                    'scope' => $segmentScope,
                ],
                'total_assets' => [
                    'value' => $bsTotals['assets'] ?? 0,
                    'change' => $bsTotals['change_percentage_assets'] ?? 0,
                    'comparison_value' => $bsTotals['comparison_assets'] ?? 0,
                    'scope' => 'company',
                ],
                'total_liabilities' => [
                    'value' => $bsTotals['liabilities'] ?? 0,
                    'change' => $bsTotals['change_percentage_liabilities'] ?? 0,
                    'comparison_value' => $bsTotals['comparison_liabilities'] ?? 0,
                    'scope' => 'company',
                ],
                'equity' => [
                    'value' => $bsTotals['equity'] ?? 0,
                    'change' => $bsTotals['change_percentage_equity'] ?? 0,
                    'comparison_value' => $bsTotals['comparison_equity'] ?? 0,
                    'scope' => 'company',
                ],
                'cash_balance' => [
                    'value' => $cashBalance,
                    'change' => 0,
                    'comparison_value' => 0,
                    'scope' => 'company',
                ],
            ],
            'cash_flow_summary' => [
                'inflow' => $cashInflow,
                'outflow' => $cashOutflow,
                'net' => $cashBalance,
                'scope' => 'company',
            ],
            'expense_breakdown' => $this->extractTopExpenses($incomeStatement['expenses'] ?? []),
            'monthly_trends' => $this->reportService->getMonthlyTrends($fiscalYearId, $branchId),
            'branch_scope' => [
                'branch_id' => $branchId,
                'segment_scope' => $segmentScope,
                'excludes_unallocated' => $isBranchScoped,
            ],
        ];
    }

    /**
     * Extract top-level expense categories for breakdown chart.
     * Only takes root-level items (level 1, no parent).
     *
     * @param  array<int, array<string, mixed>>  $expenseTree
     * @return array<int, array<string, mixed>>
     */
    private function extractTopExpenses(array $expenseTree): array
    {
        $items = [];
        foreach ($expenseTree as $node) {
            if (($node['level'] ?? 1) === 1) {
                $items[] = [
                    'name' => $node['name'] ?? 'Unknown',
                    'value' => abs($node['balance'] ?? 0),
                ];
            }
        }

        // Sort by value desc, take top 8
        usort($items, fn ($a, $b) => $b['value'] <=> $a['value']);

        return array_slice($items, 0, 8);
    }
}
