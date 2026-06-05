<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetVarianceExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private Collection $data,
    ) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Account Code', 'Account Name', 'Type', 'Period Start', 'Period End', 'Allocated', 'Actual', 'Available', 'Variance %', 'Status'];
    }

    /**
     * @param  mixed  $row
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return [
            $row['account_code'],
            $row['account_name'],
            ucfirst($row['account_type']),
            $row['period_start'],
            $row['period_end'],
            $row['allocated'],
            $row['actual'],
            $row['available'],
            $row['variance_percent'] !== null ? $row['variance_percent'] . '%' : 'N/A',
            ucwords(str_replace('_', ' ', $row['status'])),
        ];
    }

    /**
     * @return array<int, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
