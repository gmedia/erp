<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\MaintenanceCostExport;

class ExportMaintenanceCostReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'maintenance_cost_report';
    }

    protected function makeExport(array $filters): object
    {
        return new MaintenanceCostExport($filters);
    }
}
