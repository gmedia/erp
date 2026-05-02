<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\CustomerStatementReportExport;

class ExportCustomerStatementReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'customer_statement';
    }

    protected function makeExport(array $filters): object
    {
        return new CustomerStatementReportExport($filters);
    }
}