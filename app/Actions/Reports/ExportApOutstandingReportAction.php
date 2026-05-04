<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\ApOutstandingReportExport;

class ExportApOutstandingReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'ap_outstanding_report';
    }

    protected function makeExport(array $filters): object
    {
        return new ApOutstandingReportExport($filters);
    }
}
