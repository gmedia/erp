<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceFinancialReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
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
            $row['debit'],
            $row['credit'],
            $row['raw_debit'],
            $row['raw_credit'],
        ]);
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Type', 'Normal Balance', 'Level', 'Debit', 'Credit', 'Raw Debit', 'Raw Credit'];
    }
}
