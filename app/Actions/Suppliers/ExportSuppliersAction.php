<?php

namespace App\Actions\Suppliers;

use App\Exports\SupplierExport;
use App\Http\Requests\Suppliers\ExportSupplierRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ExportSuppliersAction
{
    public function execute(ExportSupplierRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Map request parameters to match SupplierExport expectations
        $filters = [
            'search' => $validated['search'] ?? null,
            'branch' => $validated['branch'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename with timestamp
        $timestamp = now()->format('Y-m-d_His');
        $filename = "suppliers_{$timestamp}.xlsx";
        $filePath = 'exports/' . $filename;

        // Store in public disk so it is accessible
        Excel::store(new SupplierExport($filters), $filePath, 'public');

        // Generate temporary download URL (valid for 1 hour)
        $url = Storage::url($filePath);

        return response()->json([
            'message' => 'Export generated successfully',
            'url' => $url,
            'filename' => $filename,
        ], 200);
    }
}
