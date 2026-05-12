<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\ExportsReportToExcel;
use App\Exports\GeneralLedgerReportExport;

class ExportGeneralLedgerReportAction
{
    use ExportsReportToExcel;

    protected function filenamePrefix(): string
    {
        return 'general_ledger_report';
    }

    protected function makeExport(array $filters): object
    {
        return new GeneralLedgerReportExport($filters);
    }
}
