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
            'branch_id' => $validated['branch_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'sort_by' => $validated['sort_by'] ?? 'created_at',
            'sort_direction' => $validated['sort_direction'] ?? 'desc',
        ];

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename with timestamp
        $filename = 'suppliers_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        // Store the file in storage/app/public/exports/
        $filePath = 'exports/' . $filename;

        // Generate the Excel file using public disk
        Excel::store(new SupplierExport($filters), $filePath, 'public');

        // Generate the public URL for download
        $url = Storage::disk('public')->url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
