<?php

namespace App\Actions\Reports;

use App\Exports\GoodsReceiptReportExport;
use App\Http\Requests\Reports\ExportGoodsReceiptReportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportGoodsReceiptReportAction
{
    public function execute(ExportGoodsReceiptReportRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated(), static fn ($value) => $value !== null && $value !== '');

        $format = $request->get('format', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        $filename = 'goods_receipt_report_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.' . $extension;
        $filePath = 'exports/' . $filename;

        Excel::store(new GoodsReceiptReportExport($filters), $filePath, 'public', $writerType);

        return response()->json([
            'url' => Storage::disk('public')->url($filePath),
            'filename' => $filename,
        ]);
    }
}
