<?php

namespace App\Actions\Reports;

use App\Exports\MaintenanceCostExport;
use App\Http\Requests\Reports\ExportMaintenanceCostRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportMaintenanceCostReportAction
{
    public function execute(ExportMaintenanceCostRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated());

        $format = $request->get('format', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        $filename = 'maintenance_cost_report_' . now()->format('Y-m-d_H-i-s') . '_' . Str::ulid() . '.' . $extension;
        $filePath = 'exports/' . $filename;

        Excel::store(new MaintenanceCostExport($filters), $filePath, 'public', $writerType);

        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
