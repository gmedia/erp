<?php

namespace App\Actions\ReportConfigurations;

use App\Actions\Concerns\ConfiguredTransactionExportAction;
use App\Exports\ReportConfigurationExport;

class ExportReportConfigurationsAction extends ConfiguredTransactionExportAction
{
    protected function filenamePrefix(): string
    {
        return 'report_configurations';
    }

    protected function makeExport(array $filters): object
    {
        return new ReportConfigurationExport($filters);
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'report_type' => null,
            'is_active' => null,
            'sort_by' => null,
            'sort_direction' => null,
        ];
    }
}
