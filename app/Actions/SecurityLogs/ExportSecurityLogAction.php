<?php

namespace App\Actions\SecurityLogs;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\SecurityLogExport;

class ExportSecurityLogAction extends ConfiguredFormattedExportAction
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function buildFilters(array $validated): array
    {
        return array_filter($validated);
    }

    protected function filenamePrefix(): string
    {
        return 'security_logs';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new SecurityLogExport($filters);
    }
}
