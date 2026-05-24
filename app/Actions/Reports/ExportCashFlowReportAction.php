<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\ExportsReportToExcel;
use App\Exports\CashFlowReportExport;

class ExportCashFlowReportAction
{
    use ExportsReportToExcel;

    protected function filenamePrefix(): string
    {
        return 'cash_flow_report';
    }

    protected function makeExport(array $filters): object
    {
        return new CashFlowReportExport($filters);
    }
}
