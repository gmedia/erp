<?php

namespace App\Exports;

use App\Exports\Concerns\ExportsNumberFormats;
use App\Exports\Concerns\WritesTextCells;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BudgetVarianceExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings, WithMapping, WithStyles
{
    use ExportsNumberFormats;
    use WritesTextCells;

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
        return [
            'Account Code',
            'Account Name',
            'Type',
            'Period Start',
            'Period End',
            'Allocated',
            'Actual',
            'Available',
            'Variance %',
            'Status',
        ];
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
            $this->numericValue($row['allocated']),
            $this->numericValue($row['actual']),
            $this->numericValue($row['available']),
            $row['variance_percent'] !== null ? $this->numericValue($row['variance_percent']) : 'N/A',
            ucwords(str_replace('_', ' ', $row['status'])),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->buildColumnFormats($this->headings());
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

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return ['Allocated', 'Actual', 'Available'];
    }

    /**
     * Variance % disimpan sebagai angka skala 0-100 (bukan pecahan), jadi memakai angka biasa,
     * bukan format persen Excel yang akan mengalikan 100 lagi. Nilai N/A tetap teks.
     *
     * @return array<int, string>
     */
    protected function numericColumnHeadings(): array
    {
        return ['Variance %'];
    }
}
