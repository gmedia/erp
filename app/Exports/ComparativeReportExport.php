<?php

namespace App\Exports;

use App\Exports\Concerns\AbstractFinancialReportExport;
use App\Services\FinancialReportService;
use Illuminate\Support\Collection;

class ComparativeReportExport extends AbstractFinancialReportExport
{
    public function collection(): Collection
    {
        $report = app(FinancialReportService::class)->getComparativeReport(
            $this->resolveFiscalYearId(),
            $this->resolveComparisonYearId(),
            $this->resolveBranchId(),
        );

        $rows = [];

        $sections = [
            'assets' => 'Assets',
            'liabilities' => 'Liabilities',
            'equity' => 'Equity',
            'revenues' => 'Revenues',
            'expenses' => 'Expenses',
        ];

        foreach ($sections as $key => $section) {
            $rows = array_merge($rows, $this->flattenTree($report[$key], $section));
        }

        $totals = $report['totals'];

        foreach ($sections as $key => $section) {
            $rows[] = [
                $section,
                '',
                'Total ' . $section,
                0,
                $totals[$key] ?? 0,
                $totals['comparison_' . $key] ?? 0,
                $totals['change_' . $key] ?? 0,
                $totals['change_percentage_' . $key] ?? 0,
            ];
        }

        return collect($rows);
    }
}
