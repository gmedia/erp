<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComparativeReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private array $filters) {}

    public function collection(): Collection
    {
        $report = app(FinancialReportService::class)->getComparativeReport(
            (int) $this->filters['fiscal_year_id'],
            isset($this->filters['comparison_year_id']) ? (int) $this->filters['comparison_year_id'] : null,
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

    public function headings(): array
    {
        return ['Section', 'Code', 'Name', 'Level', 'Balance', 'Comparison Balance', 'Change', 'Change %'];
    }

    /**
     * @return list<array<int, mixed>>
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
                $rows = array_merge($rows, $this->flattenTree($node['children'], $section, $depth + 1));
            }
        }

        return $rows;
    }
}
