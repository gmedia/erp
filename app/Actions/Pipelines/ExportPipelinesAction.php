<?php

namespace App\Actions\Pipelines;

use App\Actions\Concerns\ConfiguredTimestampExportAction;
use App\Exports\PipelineExport;

class ExportPipelinesAction extends ConfiguredTimestampExportAction
{
    /**
     * @return array<string, mixed>
     */
    protected function filterDefaults(): array
    {
        return [
            'search' => null,
            'entity_type' => null,
            'is_active' => null,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
        ];
    }

    protected function filenamePrefix(): string
    {
        return 'pipelines';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeExport(array $filters): object
    {
        return new PipelineExport($filters);
    }
}
