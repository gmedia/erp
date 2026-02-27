<?php

namespace App\Actions\PipelineAuditTrail;

use App\Exports\PipelineAuditTrailExport;
use App\Http\Requests\PipelineAuditTrail\ExportPipelineAuditTrailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportPipelineAuditTrailAction
{
    public function execute(ExportPipelineAuditTrailRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated());

        $format = $request->get('format', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        $filename = 'pipeline_audit_trail_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.' . $extension;
        $filePath = 'exports/' . $filename;

        Excel::store(new PipelineAuditTrailExport($filters), $filePath, 'public', $writerType);

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
