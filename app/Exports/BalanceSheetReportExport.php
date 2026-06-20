<?php

namespace App\Exports;

use App\Exports\Concerns\AbstractFinancialReportExport;
use App\Services\FinancialReportService;
use Illuminate\Support\Collection;

class BalanceSheetReportExport extends AbstractFinancialReportExport
{
    public function collection(): Collection
    {
        $report = app(FinancialReportService::class)->getBalanceSheet(
            $this->resolveFiscalYearId(),
            $this->resolveComparisonYearId(),
            $this->resolveBranchId(),
        );

        $rows = [];

        foreach ($report['assets'] as $node) {
            $rows = array_merge($rows, $this->flattenTree([$node], 'Assets'));
        }

        foreach ($report['liabilities'] as $node) {
            $rows = array_merge($rows, $this->flattenTree([$node], 'Liabilities'));
        }

        foreach ($report['equity'] as $node) {
            $rows = array_merge($rows, $this->flattenTree([$node], 'Equity'));
        }

        $totals = $report['totals'];

        $rows[] = $this->summaryRow('Assets', 'Total Assets', $totals, 'assets');
        $rows[] = $this->summaryRow('Liabilities', 'Total Liabilities', $totals, 'liabilities');
        $rows[] = $this->summaryRow('Equity', 'Total Equity', $totals, 'equity');

        $liabilitiesPlusEquity = $totals['liabilities'] + $totals['equity'];
        $comparisonLiabilitiesPlusEquity = $totals['comparison_liabilities'] + $totals['comparison_equity'];
        $changeLiabilitiesPlusEquity = ($totals['change_liabilities'] ?? 0) + ($totals['change_equity'] ?? 0);

        $rows[] = [
            'Total',
            '',
            'Grand Total (Assets = Liabilities + Equity)',
            0,
            $liabilitiesPlusEquity,
            $comparisonLiabilitiesPlusEquity,
            $changeLiabilitiesPlusEquity,
            0,
        ];

        return collect($rows);
    }

    /**
     * @param  array<string, mixed>  $totals
     * @return array<int, mixed>
     */
    private function summaryRow(string $section, string $label, array $totals, string $key): array
    {
        return [
            $section,
            '',
            $label,
            0,
            $totals[$key],
            $totals['comparison_' . $key],
            $totals['change_' . $key],
            $totals['change_percentage_' . $key],
        ];
    }
}
