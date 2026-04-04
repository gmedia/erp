<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\InventoryStocktakeVarianceExport;

class ExportInventoryStocktakeVarianceReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'inventory_stocktake_variance_report';
    }

    protected function makeExport(array $filters): object
    {
        return new InventoryStocktakeVarianceExport($filters);
    }
}
