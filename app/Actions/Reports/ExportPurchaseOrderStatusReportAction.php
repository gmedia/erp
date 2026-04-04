<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\PurchaseOrderStatusReportExport;

class ExportPurchaseOrderStatusReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'purchase_order_status_report';
    }

    protected function makeExport(array $filters): object
    {
        return new PurchaseOrderStatusReportExport($filters);
    }
}
