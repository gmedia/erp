<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\ExportsReportToExcel;
use App\Exports\IncomeStatementReportExport;

class ExportIncomeStatementReportAction
{
    use ExportsReportToExcel;

    protected function filenamePrefix(): string
    {
        return 'income_statement_report';
    }

    protected function makeExport(array $filters): object
    {
        return new IncomeStatementReportExport($filters);
    }
}
