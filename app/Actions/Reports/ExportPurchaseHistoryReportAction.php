<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\PurchaseHistoryReportExport;

class ExportPurchaseHistoryReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'purchase_history_report';
    }

    protected function makeExport(array $filters): object
    {
        return new PurchaseHistoryReportExport($filters);
    }
}
