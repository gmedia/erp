<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BalanceSheetReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private array $filters) {}

    public function collection(): Collection
    {
        $report = app(FinancialReportService::class)->getBalanceSheet(
            (int) $this->filters['fiscal_year_id'],
            isset($this->filters['comparison_year_id']) ? (int) $this->filters['comparison_year_id'] : null
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

        $rows[] = [
            'section' => 'Assets',
            'code' => '',
            'name' => 'Total Assets',
            'level' => 0,
            'balance' => $totals['assets'],
            'comparison_balance' => $totals['comparison_assets'],
            'change' => $totals['change_assets'],
            'change_percentage' => $totals['change_percentage_assets'],
        ];

        $rows[] = [
            'section' => 'Liabilities',
            'code' => '',
            'name' => 'Total Liabilities',
            'level' => 0,
            'balance' => $totals['liabilities'],
            'comparison_balance' => $totals['comparison_liabilities'],
            'change' => $totals['change_liabilities'],
            'change_percentage' => $totals['change_percentage_liabilities'],
        ];

        $rows[] = [
            'section' => 'Equity',
            'code' => '',
            'name' => 'Total Equity',
            'level' => 0,
            'balance' => $totals['equity'],
            'comparison_balance' => $totals['comparison_equity'],
            'change' => $totals['change_equity'],
            'change_percentage' => $totals['change_percentage_equity'],
        ];

        $rows[] = [
            'section' => 'Total',
            'code' => '',
            'name' => 'Grand Total (Assets = Liabilities + Equity)',
            'level' => 0,
            'balance' => $totals['liabilities'] + $totals['equity'],
            'comparison_balance' => $totals['comparison_liabilities'] + $totals['comparison_equity'],
            'change' => ($totals['change_liabilities'] ?? 0) + ($totals['change_equity'] ?? 0),
            'change_percentage' => 0,
        ];

        return collect($rows)->map(fn (array $row): array => [
            $row['section'],
            $row['code'],
            $row['name'],
            $row['level'],
            $row['balance'],
            $row['comparison_balance'],
            $row['change'],
            $row['change_percentage'],
        ]);
    }

    public function headings(): array
    {
        return ['Section', 'Code', 'Name', 'Level', 'Balance', 'Comparison Balance', 'Change', 'Change %'];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    private function flattenTree(array $nodes, string $section, int $depth = 0): array
    {
        $rows = [];

        foreach ($nodes as $node) {
            $rows[] = [
                'section' => $section,
                'code' => $node['code'],
                'name' => str_repeat('    ', $depth) . $node['name'],
                'level' => $node['level'],
                'balance' => $node['balance'],
                'comparison_balance' => $node['comparison_balance'],
                'change' => $node['change'] ?? 0,
                'change_percentage' => $node['change_percentage'] ?? 0,
            ];

            if (! empty($node['children'])) {
                $rows = array_merge($rows, $this->flattenTree($node['children'], $section, $depth + 1));
            }
        }

        return $rows;
    }
}
