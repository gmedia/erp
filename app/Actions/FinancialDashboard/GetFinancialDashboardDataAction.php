<?php

namespace App\Actions\FinancialDashboard;

use App\Services\FinancialReportService;

class GetFinancialDashboardDataAction
{
    private const TOP_EXPENSE_LIMIT = 5;

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
     * Rincian beban per akun daun (top N + Lainnya), bukan satu batang akar.
     *
     * @param  array<int, array<string, mixed>>  $expenseTree
     * @return array<int, array<string, mixed>>
     */
    private function extractTopExpenses(array $expenseTree): array
    {
        $items = [];
        $this->collectLeafExpenses($expenseTree, $items);

        usort($items, fn (array $a, array $b): int => $b['value'] <=> $a['value']);

        if (count($items) <= self::TOP_EXPENSE_LIMIT) {
            return $items;
        }

        $topItems = array_slice($items, 0, self::TOP_EXPENSE_LIMIT);
        $otherItems = array_slice($items, self::TOP_EXPENSE_LIMIT);
        $otherTotal = array_sum(array_column($otherItems, 'value'));

        if ($otherTotal > 0) {
            $topItems[] = ['name' => 'Others', 'value' => round($otherTotal, 2)];
        }

        return $topItems;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<string, mixed>>  $items
     */
    private function collectLeafExpenses(array $nodes, array &$items): void
    {
        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            if ($children !== []) {
                $this->collectLeafExpenses($children, $items);

                continue;
            }

            $value = abs((float) ($node['balance'] ?? 0));
            if ($value <= 0) {
                continue;
            }

            $items[] = [
                'name' => $node['name'] ?? 'Unknown',
                'value' => $value,
            ];
        }
    }
}
