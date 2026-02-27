<?php

namespace App\Actions\Warehouses;

use App\Exports\WarehouseExport;
use App\Http\Requests\Warehouses\ExportWarehouseRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Action to export warehouses to Excel based on filters.
 */
class ExportWarehousesAction
{
    public function execute(ExportWarehouseRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        $filters = array_filter($filters);

        $filename = 'warehouses_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        Excel::store(new WarehouseExport($filters), $filePath, 'public');

        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
