<?php

namespace App\Actions\AuditLog;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\AuditLogExport;

class ExportAuditLogAction extends ConfiguredFormattedExportAction
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
        return 'audit_logs';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new AuditLogExport($filters);
    }
}
