<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\InventoryValuationReportExport;

class ExportInventoryValuationReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'inventory_valuation_report';
    }

    protected function makeExport(array $filters): object
    {
        return new InventoryValuationReportExport($filters);
    }
}
