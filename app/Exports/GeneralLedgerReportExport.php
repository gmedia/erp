<?php

namespace App\Exports;

use App\Actions\Reports\GetGeneralLedgerReportAction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GeneralLedgerReportExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    public function __construct(private array $filters) {}

    public function collection()
    {
        return app(GetGeneralLedgerReportAction::class)
            ->execute($this->filters)
            ->map(fn (array $row): array => [
                $row['entry_date'],
                $row['entry_number'],
                $row['reference'],
                $row['description'],
                $row['account_code'],
                $row['account_name'],
                $row['debit'],
                $row['credit'],
                $row['running_balance'],
                $row['memo'],
            ]);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Entry Number',
            'Reference',
            'Description',
            'Account Code',
            'Account Name',
            'Debit',
            'Credit',
            'Running Balance',
            'Memo',
        ];
    }
}
