<?php

namespace App\Exports;

use App\Exports\Concerns\AbstractFinancialReportExport;
use App\Services\FinancialReportService;
use Illuminate\Support\Collection;

class IncomeStatementReportExport extends AbstractFinancialReportExport
{
    public function collection(): Collection
    {
        $report = app(FinancialReportService::class)->getIncomeStatement(
            $this->resolveFiscalYearId(),
            $this->resolveComparisonYearId(),
        );

        $rows = [];

        $rows = array_merge($rows, $this->flattenTree($report['revenues'], 'Revenues'));
        $rows = array_merge($rows, $this->flattenTree($report['expenses'], 'Expenses'));

        $totals = $report['totals'];

        $rows[] = $this->summaryRow('Revenues', 'Total Revenues', $totals, 'revenue');
        $rows[] = $this->summaryRow('Expenses', 'Total Expenses', $totals, 'expense');
        $rows[] = $this->summaryRow('Net Income', 'Net Income', $totals, 'net_income');

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
