<?php

namespace App\Actions\ApprovalAuditTrail;

use App\Exports\ApprovalAuditTrailExport;
use App\Http\Requests\ApprovalAuditTrail\ExportApprovalAuditTrailRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class ExportApprovalAuditTrailAction
{
    public function execute(ExportApprovalAuditTrailRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated());

        $format = $request->get('format', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? ExcelExcel::CSV : ExcelExcel::XLSX;

        $filename = 'approval_audit_trail_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.' . $extension;
        $filePath = 'exports/' . $filename;

        Excel::store(new ApprovalAuditTrailExport($filters), $filePath, 'public', $writerType);

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
