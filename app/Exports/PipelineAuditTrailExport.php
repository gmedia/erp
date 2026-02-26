<?php

namespace App\Exports;

use App\Actions\PipelineAuditTrail\IndexPipelineAuditTrailAction;
use App\Http\Requests\PipelineAuditTrail\IndexPipelineAuditTrailRequest;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PipelineAuditTrailExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->filters['export'] = true; // Flag for action to return all without pagination
    }

    public function collection()
    {
        $action = app(IndexPipelineAuditTrailAction::class);

        $request = new IndexPipelineAuditTrailRequest();
        $request->merge($this->filters);

        return $action->execute($request);
    }

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
            $log->pipelineEntityState?->pipeline?->name ?? '-',
            Str::afterLast($log->entity_type, '\\'),
            $log->entity_id,
            $log->fromState?->name ?? 'Initial',
            $log->toState?->name ?? '-',
            $log->transition?->name ?? '-',
            $log->performedBy?->name ?? 'System',
            $log->comment ?? '-',
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
