<?php

namespace App\Exports;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CashFlowReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private array $filters) {}

    public function collection(): Collection
    {
        $rows = app(FinancialReportService::class)->getCashFlow((int) $this->filters['fiscal_year_id']);

        return collect($rows)->map(fn (array $row): array => [
            $row['code'],
            $row['name'],
            $row['type'],
            $row['normal_balance'],
            $row['level'],
            $row['inflow'],
            $row['outflow'],
            $row['inflow'] - $row['outflow'],
        ]);
    }

    public function headings(): array
    {
        return ['Code', 'Name', 'Type', 'Normal Balance', 'Level', 'Inflow', 'Outflow', 'Net'];
    }
}
