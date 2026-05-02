<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\ApPaymentHistoryReportExport;

class ExportApPaymentHistoryReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'ap_payment_history_report';
    }

    protected function makeExport(array $filters): object
    {
        return new ApPaymentHistoryReportExport($filters);
    }
}
