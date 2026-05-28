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

        $rows[] = ['Revenues', '', 'Total Revenues', 0, $totals['revenue'], $totals['comparison_revenue'], $totals['change_revenue'], $totals['change_percentage_revenue']];
        $rows[] = ['Expenses', '', 'Total Expenses', 0, $totals['expense'], $totals['comparison_expense'], $totals['change_expense'], $totals['change_percentage_expense']];
        $rows[] = ['Net Income', '', 'Net Income', 0, $totals['net_income'], $totals['comparison_net_income'], $totals['change_net_income'], $totals['change_percentage_net_income']];

        return collect($rows);
    }
}
