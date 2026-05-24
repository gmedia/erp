<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class IncomeStatementReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private array $filters) {}

    public function collection(): Collection
    {
        $report = app(FinancialReportService::class)->getIncomeStatement(
            (int) $this->filters['fiscal_year_id'],
            isset($this->filters['comparison_year_id']) ? (int) $this->filters['comparison_year_id'] : null,
        );

        $rows = [];

        foreach ($this->flattenTree($report['revenues'], 'Revenues') as $row) {
            $rows[] = $row;
        }

        foreach ($this->flattenTree($report['expenses'], 'Expenses') as $row) {
            $rows[] = $row;
        }

        $totals = $report['totals'];

        $rows[] = ['Revenues', '', 'Total Revenues', 0, $totals['revenue'], $totals['comparison_revenue'], $totals['change_revenue'], $totals['change_percentage_revenue']];
        $rows[] = ['Expenses', '', 'Total Expenses', 0, $totals['expense'], $totals['comparison_expense'], $totals['change_expense'], $totals['change_percentage_expense']];
        $rows[] = ['Net Income', '', 'Net Income', 0, $totals['net_income'], $totals['comparison_net_income'], $totals['change_net_income'], $totals['change_percentage_net_income']];

        return collect($rows);
    }

    public function headings(): array
    {
        return ['Section', 'Code', 'Name', 'Level', 'Balance', 'Comparison Balance', 'Change', 'Change %'];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string|int|float|null>>
     */
    private function flattenTree(array $nodes, string $section, int $depth = 0): array
    {
        $rows = [];

        foreach ($nodes as $node) {
            $rows[] = [
                $section,
                $node['code'] ?? '',
                str_repeat('    ', $depth) . ($node['name'] ?? ''),
                $node['level'] ?? $depth,
                $node['balance'] ?? 0,
                $node['comparison_balance'] ?? 0,
                $node['change'] ?? 0,
                $node['change_percentage'] ?? 0,
            ];

            if (! empty($node['children'])) {
                foreach ($this->flattenTree($node['children'], $section, $depth + 1) as $childRow) {
                    $rows[] = $childRow;
                }
            }
        }

        return $rows;
    }
}
