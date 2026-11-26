<?php

namespace App\Exports;

use App\Actions\Reports\GetTrialBalanceReportAction;
use App\Exports\Concerns\ExportsNumberFormats;
use App\Exports\Concerns\WritesTextCells;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceReportExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithCustomValueBinder, WithHeadings
{
    use ExportsNumberFormats;
    use WritesTextCells;

    public function __construct(
        private array $filters,
    ) {}

    public function collection(): Collection
    {
        $report = app(GetTrialBalanceReportAction::class)->execute($this->filters);

        return collect($report['data'])->map(fn (array $row): array => [
            $row['account_code'],
            $row['account_name'],
            $row['account_type'],
            $this->numericValue($row['opening_balance']),
            $this->numericValue($row['debit_total']),
            $this->numericValue($row['credit_total']),
            $this->numericValue($row['closing_balance']),
            $this->numericValue($row['debit_balance']),
            $this->numericValue($row['credit_balance']),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return $this->buildColumnFormats($this->headings());
    }

    public function headings(): array
    {
        return [
            'Account Code',
            'Account Name',
            'Type',
            'Opening Balance',
            'Debit Total',
            'Credit Total',
            'Closing Balance',
            'Debit Balance',
            'Credit Balance',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function currencyColumnHeadings(): array
    {
        return [
            'Opening Balance',
            'Debit Total',
            'Credit Total',
            'Closing Balance',
            'Debit Balance',
            'Credit Balance',
        ];
    }
}
