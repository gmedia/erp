<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\ExportsReportToExcel;
use App\Exports\BalanceSheetReportExport;

class ExportBalanceSheetReportAction
{
    use ExportsReportToExcel;

    protected function filenamePrefix(): string
    {
        return 'balance_sheet_report';
    }

    protected function makeExport(array $filters): object
    {
        return new BalanceSheetReportExport($filters);
    }
}
