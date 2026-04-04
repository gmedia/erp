<?php

namespace App\Exports;

use App\Actions\ApprovalAuditTrail\IndexApprovalAuditTrailAction;
use App\Exports\Concerns\AbstractActionCollectionExport;
use App\Http\Requests\ApprovalAuditTrail\IndexApprovalAuditTrailRequest;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ApprovalAuditTrailExport extends AbstractActionCollectionExport implements WithHeadings, WithMapping
{
    public function headings(): array
    {
        return [
            'Date',
            'Document Type',
            'Document ID',
            'Event',
            'Actor',
            'Step Order',
            'Metadata',
            'IP Address',
            'User Agent',
        ];
    }

    /**
     * @param  \App\Models\ApprovalAuditLog  $log
     */
    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            Str::afterLast($log->approvable_type, '\\'),
            $log->approvable_id,
            ucfirst(str_replace('_', ' ', $log->event ?? '-')),
            $log->actor->name ?? 'System',
            $log->step_order ?? '-',
            is_array($log->metadata) ? json_encode($log->metadata) : ($log->metadata ?? '-'),
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
        return IndexApprovalAuditTrailAction::class;
    }

    protected function requestClass(): string
    {
        return IndexApprovalAuditTrailRequest::class;
    }
}
