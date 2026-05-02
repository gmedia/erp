<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\ArAgingReportExport;

class ExportArAgingReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'ar_aging';
    }

    protected function makeExport(array $filters): object
    {
        return new ArAgingReportExport($filters);
    }
}