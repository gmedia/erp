<?php

namespace App\Exports;

use App\Actions\ApprovalAuditTrail\IndexApprovalAuditTrailAction;
use App\Http\Requests\ApprovalAuditTrail\IndexApprovalAuditTrailRequest;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApprovalAuditTrailExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true; // Flag for action to return all without pagination
    }

    public function collection()
    {
        $action = app(IndexApprovalAuditTrailAction::class);

        $request = new IndexApprovalAuditTrailRequest;
        $request->merge($this->filters);

        return $action->execute($request);
    }

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

    public function map($log): array
    {
        return [
            $log->created_at?->format('Y-m-d H:i:s') ?? '-',
            Str::afterLast($log->approvable_type, '\\'),
            $log->approvable_id,
            ucfirst(str_replace('_', ' ', $log->event ?? '-')),
            $log->actor?->name ?? 'System',
            $log->step_order ?? '-',
            is_array($log->metadata) ? json_encode($log->metadata) : ($log->metadata ?? '-'),
            $log->ip_address ?? '-',
            $log->user_agent ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
