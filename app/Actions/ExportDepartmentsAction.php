<?php

namespace App\Actions;

use App\Exports\DepartmentExport;
use App\Http\Requests\ExportDepartmentRequest;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export departments to Excel based on filters
 */
class ExportDepartmentsAction
{
    /**
     * Execute the department export action
     *
     * @param \App\Http\Requests\ExportDepartmentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(ExportDepartmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename with timestamp
        $filename = 'departments_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new \App\Exports\DepartmentExport($filters);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = \Illuminate\Support\Facades\Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
