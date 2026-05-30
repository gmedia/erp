<?php

namespace App\Exports;

use App\Actions\Reports\GetTrialBalanceReportAction;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TrialBalanceReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private array $filters) {}

    public function collection(): Collection
    {
        $report = app(GetTrialBalanceReportAction::class)->execute($this->filters);

        return collect($report['data'])->map(fn (array $row): array => [
            $row['account_code'],
            $row['account_name'],
            $row['account_type'],
            $row['opening_balance'],
            $row['debit_total'],
            $row['credit_total'],
            $row['debit_balance'],
            $row['credit_balance'],
        ]);
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
            'Debit Balance',
            'Credit Balance',
        ];
    }
}
