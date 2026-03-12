<?php

namespace App\Actions\Reports;

use App\Exports\PurchaseHistoryReportExport;
use App\Http\Requests\Reports\ExportPurchaseHistoryReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel;

class ExportPurchaseHistoryReportAction
{
    public function execute(ExportPurchaseHistoryReportRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated(), static fn ($value) => $value !== null && $value !== '');

        $format = $request->get('format', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? ExcelExcel::CSV : ExcelExcel::XLSX;

        $filename = 'purchase_history_report_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.' . $extension;
        $filePath = 'exports/' . $filename;

        Excel::store(new PurchaseHistoryReportExport($filters), $filePath, 'public', $writerType);

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
