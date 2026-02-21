<?php

namespace App\Actions\Reports;

use App\Exports\BookValueDepreciationExport;
use App\Http\Requests\Reports\ExportBookValueDepreciationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportBookValueDepreciationReportAction
{
    public function execute(ExportBookValueDepreciationRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated());

        $filename = 'book_value_depreciation_report_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new BookValueDepreciationExport($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
