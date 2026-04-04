<?php

namespace App\Exports;

use App\Actions\PipelineAuditTrail\IndexPipelineAuditTrailAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\PipelineAuditTrail\IndexPipelineAuditTrailRequest;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PipelineAuditTrailExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Date',
            'Pipeline',
            'Entity Type',
            'Entity ID',
            'From State',
            'To State',
            'Transition',
            'Performed By',
            'Comment',
            'IP Address',
            'User Agent',
        ];
    }

    public function map($log): array
    {
        return [
            $log->created_at?->format('Y-m-d H:i:s') ?? '-',
            $log->pipelineEntityState->pipeline->name ?? '-',
            Str::afterLast($log->entity_type, '\\'),
            $log->entity_id,
            $log->fromState->name ?? 'Initial',
            $log->toState->name ?? '-',
            $log->transition->name ?? '-',
            $log->performedBy->name ?? 'System',
            $log->comment ?? '-',
            $log->ip_address ?? '-',
            $log->user_agent ?? '-',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function prepareFilters(array $filters): array
    {
        $filters['export'] = true;

        return $filters;
    }

    protected function actionClass(): string
    {
        return IndexPipelineAuditTrailAction::class;
    }

    protected function requestClass(): string
    {
        return IndexPipelineAuditTrailRequest::class;
    }
}
