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

        $rows[] = ['Assets', '', 'Total Assets', 0, $totals['assets'], $totals['comparison_assets'], $totals['change_assets'], $totals['change_percentage_assets']];
        $rows[] = ['Liabilities', '', 'Total Liabilities', 0, $totals['liabilities'], $totals['comparison_liabilities'], $totals['change_liabilities'], $totals['change_percentage_liabilities']];
        $rows[] = ['Equity', '', 'Total Equity', 0, $totals['equity'], $totals['comparison_equity'], $totals['change_equity'], $totals['change_percentage_equity']];
        $rows[] = ['Total', '', 'Grand Total (Assets = Liabilities + Equity)', 0, $totals['liabilities'] + $totals['equity'], $totals['comparison_liabilities'] + $totals['comparison_equity'], ($totals['change_liabilities'] ?? 0) + ($totals['change_equity'] ?? 0), 0];

        return collect($rows);
    }
}
