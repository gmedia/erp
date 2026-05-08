<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\ApAgingReportExport;

class ExportApAgingReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'ap_aging_report';
    }

    protected function makeExport(array $filters): object
    {
        return new ApAgingReportExport($filters);
    }
}
