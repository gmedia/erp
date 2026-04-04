<?php

namespace App\Actions\ApprovalFlows;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\ApprovalFlowExport;

class ExportApprovalFlowsAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'approvable_type' => null,
            'is_active' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'approval_flows';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new ApprovalFlowExport($filters);
    }
}
