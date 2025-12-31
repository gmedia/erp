<?php

namespace App\Actions;

use App\Exports\PositionExport;
use App\Http\Requests\ExportPositionRequest;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export positions to Excel based on filters
 */
class ExportPositionsAction
{
    /**
     * Execute the position export action
     *
     * @param \App\Http\Requests\ExportPositionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(ExportPositionRequest $request): JsonResponse
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
        $filename = 'positions_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new \App\Exports\PositionExport($filters);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = \Illuminate\Support\Facades\Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
