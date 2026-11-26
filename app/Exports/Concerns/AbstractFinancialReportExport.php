<?php

namespace App\Exports\Concerns;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

abstract class AbstractFinancialReportExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings
{
    use ExportsNumberFormats;
    use WritesTextCells;

    public function __construct(
        protected array $filters,
    ) {}

    abstract public function collection(): Collection;

    public function headings(): array
    {
        return ['Section', 'Code', 'Name', 'Level', 'Balance', 'Comparison Balance', 'Change', 'Change %'];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->buildColumnFormats($this->headings());
    }

    /**
     * Kolom nilai saldo dan selisih memakai format mata uang; persen memakai angka biasa.
     *
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return ['Balance', 'Comparison Balance', 'Change'];
    }

    /**
     * Change % disimpan sebagai angka skala 0-100 (bukan pecahan), jadi tidak memakai format persen
     * Excel yang akan mengalikan 100 lagi. Ditulis sebagai angka biasa dengan dua desimal.
     *
     * @return array<int, string>
     */
    protected function numericColumnHeadings(): array
    {
        return ['Change %'];
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
                $this->numericValue($node['balance'] ?? 0),
                $this->numericValue($node['comparison_balance'] ?? 0),
                $this->numericValue($node['change'] ?? 0),
                $this->numericValue($node['change_percentage'] ?? 0),
            ];

            if (! empty($node['children'])) {
                $rows = array_merge($rows, $this->flattenTree($node['children'], $section, $depth + 1));
            }
        }

        return $rows;
    }

    /**
     * Baris ringkasan (total/laba) dari array totals berpola `{key}`, `comparison_{key}`,
     * `change_{key}`, `change_percentage_{key}`.
     *
     * @param  array<string, mixed>  $totals
     * @return array<int, mixed>
     */
    protected function summaryRow(string $section, string $label, array $totals, string $key): array
    {
        return [
            $section,
            '',
            $label,
            0,
            $this->numericValue($totals[$key] ?? 0),
            $this->numericValue($totals['comparison_' . $key] ?? 0),
            $this->numericValue($totals['change_' . $key] ?? 0),
            $this->numericValue($totals['change_percentage_' . $key] ?? 0),
        ];
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
