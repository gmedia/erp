<?php

namespace App\Actions\PipelineAuditTrail;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\PipelineAuditTrailExport;

class ExportPipelineAuditTrailAction extends ConfiguredFormattedExportAction
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
        return 'pipeline_audit_trail';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new PipelineAuditTrailExport($filters);
    }
}
