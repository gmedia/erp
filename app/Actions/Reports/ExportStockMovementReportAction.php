<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\StockMovementReportExport;

class ExportStockMovementReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'stock_movement_report';
    }

    protected function makeExport(array $filters): object
    {
        return new StockMovementReportExport($filters);
    }
}
