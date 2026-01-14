<?php

namespace App\Actions\Positions;

use App\Domain\Positions\PositionFilterService;
use App\Exports\PositionExport;
use App\Http\Requests\Positions\ExportPositionRequest;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export positions to Excel based on filters.
 */
class ExportPositionsAction
{
    public function __construct(
        private PositionFilterService $filterService
    ) {}

    /**
     * Execute the position export action.
     *
     * @param  \App\Http\Requests\Positions\ExportPositionRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(ExportPositionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Position::query();

        if ($request->filled('search')) {
            $this->filterService->applySearch($query, $validated['search'], ['name']);
        }

        $this->filterService->applySorting(
            $query,
            $validated['sort_by'] ?? 'created_at',
            $validated['sort_direction'] ?? 'desc',
            ['id', 'name', 'created_at', 'updated_at']
        );

        // Generate filename with timestamp
        $filename = 'positions_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        $export = new PositionExport([], $query);
        Excel::store($export, $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
