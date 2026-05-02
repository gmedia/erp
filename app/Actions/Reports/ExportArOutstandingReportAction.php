<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\ArOutstandingReportExport;

class ExportArOutstandingReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'ar_outstanding';
    }

    protected function makeExport(array $filters): object
    {
        return new ArOutstandingReportExport($filters);
    }
}