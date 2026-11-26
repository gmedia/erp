<?php

namespace App\Exports;

use App\Exports\Concerns\ExportsNumberFormats;
use App\Exports\Concerns\WritesTextCells;
use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceFinancialReportExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings
{
    use ExportsNumberFormats;
    use WritesTextCells;

    public function __construct(
        private array $filters,
    ) {}

    public function collection(): Collection
    {
        $branchId = isset($this->filters['branch_id']) && $this->filters['branch_id'] !== ''
            ? (int) $this->filters['branch_id']
            : null;

        $rows = app(FinancialReportService::class)->getTrialBalance((int) $this->filters['fiscal_year_id'], $branchId);

        return collect($rows)->map(fn (array $row): array => [
            $row['code'],
            $row['name'],
            $row['type'],
            $row['normal_balance'],
            $row['level'],
            $this->numericValue($row['debit']),
            $this->numericValue($row['credit']),
            $this->numericValue($row['raw_debit']),
            $this->numericValue($row['raw_credit']),
        ]);
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Type', 'Normal Balance', 'Level', 'Debit', 'Credit', 'Raw Debit', 'Raw Credit'];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->buildColumnFormats($this->headings());
    }

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return ['Debit', 'Credit', 'Raw Debit', 'Raw Credit'];
    }
}
