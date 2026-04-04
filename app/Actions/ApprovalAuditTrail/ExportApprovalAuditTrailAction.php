<?php

namespace App\Actions\ApprovalAuditTrail;

use App\Actions\Concerns\ConfiguredFormattedExportAction;
use App\Exports\ApprovalAuditTrailExport;

class ExportApprovalAuditTrailAction extends ConfiguredFormattedExportAction
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
        return 'approval_audit_trail';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new ApprovalAuditTrailExport($filters);
    }
}
