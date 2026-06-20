<?php

namespace App\Exports\Concerns;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class AbstractFinancialReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(
        protected array $filters,
    ) {}

    abstract public function collection(): Collection;

    public function headings(): array
    {
        return ['Section', 'Code', 'Name', 'Level', 'Balance', 'Comparison Balance', 'Change', 'Change %'];
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<int, mixed>>
     */
    protected function flattenTree(array $nodes, string $section, int $depth = 0): array
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

    protected function resolveComparisonYearId(): ?int
    {
        return isset($this->filters['comparison_year_id']) ? (int) $this->filters['comparison_year_id'] : null;
    }

    protected function resolveFiscalYearId(): int
    {
        return (int) $this->filters['fiscal_year_id'];
    }

    protected function resolveBranchId(): ?int
    {
        $branchId = $this->filters['branch_id'] ?? null;

        if ($branchId === null || $branchId === '') {
            return null;
        }

        return (int) $branchId;
    }
}
