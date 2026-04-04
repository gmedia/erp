<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\StockAdjustmentReportExport;

class ExportStockAdjustmentReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'stock_adjustment_report';
    }

    protected function makeExport(array $filters): object
    {
        return new StockAdjustmentReportExport($filters);
    }
}
