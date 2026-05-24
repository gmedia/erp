<?php

namespace App\Actions\Reports;

use App\Actions\Concerns\ExportsReportToExcel;
use App\Exports\ComparativeReportExport;

class ExportComparativeReportAction
{
    use ExportsReportToExcel;

    protected function filenamePrefix(): string
    {
        return 'comparative_report';
    }

    protected function makeExport(array $filters): object
    {
        return new ComparativeReportExport($filters);
    }
}
